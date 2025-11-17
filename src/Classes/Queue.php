<?php

namespace Aeros\Src\Classes;

use Aeros\Src\Classes\Job;

class Queue
{
    /** @var string */
    const DEFAULT_PIPELINE = 'gx_pipelines';

    /** @var string */
    const LOCKED_STATE = 'locked';

    /** @var string */
    const COMPLETED_STATE = 'completed';

    /** @var string */
    const FAILED_STATE = 'failed';

    /** @var int */
    const DEFAULT_BLOCK_TIMEOUT = 5; // seconds

    /**
     * Blocking pop - waits for a job without polling.
     * This is THE solution for real-time queue processing.
     *
     * @param string|array $pipelineName
     * @param int $timeout Seconds to wait (0 = infinite)
     * @return Job|bool
     */
    public function blockingPop(string|array $pipelineName = '*', int $timeout = self::DEFAULT_BLOCK_TIMEOUT): Job|bool
    {
        if ($pipelineName === '*' || $pipelineName === 'all') {
            $pipelineName = [env('APP_NAME') . '_' . self::DEFAULT_PIPELINE];
        }

        if (is_string($pipelineName)) {
            $this->parsePipelineName($pipelineName);
            $pipelineName = [$pipelineName];
        }

        // Use 'queue' connection instead of 'redis'
        $result = cache('queue')->blpop($pipelineName, $timeout);

        if (empty($result) || ! is_array($result) || count($result) !== 2) {
            return false;
        }

        // $result[0] = pipeline name, $result[1] = job
        $job = @unserialize($result[1]);

        return $job instanceof Job ? $job : false;
    }

    /**
     * Process pipeline using blocking pop (recommended for workers).
     *
     * @param string|array $pipelineName
     * @param int $timeout Block timeout in seconds
     * @return mixed
     */
    public function processPipelineBlocking(string|array $pipelineName = '*', int $timeout = self::DEFAULT_BLOCK_TIMEOUT): mixed
    {
        $job = $this->blockingPop($pipelineName, $timeout);

        if (! $job) {
            return false;
        }

        // Check if job is locked
        if ($this->isJobLocked($job->uuid)) {
            // Put it back at the end
            cache('queue')->rpush(env('APP_NAME') . '_' . self::DEFAULT_PIPELINE, serialize($job));
            return false;
        }

        // Lock the job
        if (! $this->setState($job->uuid, self::LOCKED_STATE)) {
            // Put it back if we can't lock it
            cache('queue')->rpush(env('APP_NAME') . '_' . self::DEFAULT_PIPELINE, serialize($job));
            return false;
        }

        $time = time();
        $jobName = get_class($job);
        $pipelineName = env('APP_NAME') . '_' . self::DEFAULT_PIPELINE;

        try {
            // Execute the job
            if ($jobStatus = $job->doWork()) {
                $this->setState(
                    "{$pipelineName}:job:{$jobName}:job_uuid:{$job->uuid}:at:{$time}",
                    self::COMPLETED_STATE
                );

                return true;
            }

            // Job failed - put it back in the queue
            cache('queue')->rpush($pipelineName, serialize($job));

            $this->setState(
                "{$pipelineName}:job:{$job->uuid}",
                self::FAILED_STATE
            );

            return false;

        } finally {
            // Always unlock the job
            $this->delState($job->uuid, self::LOCKED_STATE);
        }
    }

    /**
     * Adds a Job or an array of jobs to a pipeline tail in the queue system.
     *
     * @param array|string|Job $jobs One or an array of Jobs
     * @param string $pipelineName
     * @return boolean
     */
    public function push(array|string|Job $jobs, string $pipelineName = '*'): bool
    {
        $this->parsePipelineName($pipelineName);

        $jobs = is_array($jobs) ? $jobs : [$jobs];

        // Adds jobs to a pipeline
        foreach ($jobs as $job) {

            if ($job instanceof Job) {
                $this->pushJob($pipelineName, $job);

                continue;
            }

            if (class_exists($job) && is_subclass_of($job, Job::class)) {
                $this->pushJob($pipelineName, new $job);
            }
        }

        return true;
    }

    /**
     * Pushes a job to a pipeline.
     *
     * @param string $pipelineName
     * @param Job $job
     * @return bool
     */
    public function pushJob(string $pipelineName, Job $job): bool
    {
        $start = 0;
        $chunk = 1000;
        $serializedJob = serialize($job);

        while (true) {
            // Get jobs from the pipeline
            $jobs = cache('queue')->lrange($pipelineName, $start, $start + $chunk - 1);

            if (empty($jobs)) {
                break;
            }

            // Get the last part of the class name
            $jobName = basename(str_replace('\\', '/', get_class($job)));

            foreach ($jobs as $job) {
                if (str_contains($job, $jobName)) {
                    return false;
                }
            }

            $start += $chunk;
        }

        return cache('queue')->rpush($pipelineName, $serializedJob);
    }

    /**
     * Executes and removes a Job from the pipeline's head.
     *
     * @param string $pipelineName Example: "pipeline1"
     * @return Job|bool
     * @throws Exception
     */
    public function pop(string $pipelineName = '*'): Job|bool
    {
        $job = cache('queue')->lpop($pipelineName);

        if (empty($job) || is_array($job)) {
            return false;
        }

        // Return and remove a job from the pipeline, if there is any
        return @unserialize($job);
    }

    /**
     * Processes and execute all jobs from a pipeline.
     *
     * NOTE: This method uses polling and should be avoided for workers.
     * Use processPipelineBlocking() instead for better performance.
     *
     * @param array|string $pipelineName <''> Empty or no value it will process the default pipeline
     *                              <'all'|'*'> it will process all pipelines
     * @return mixed
     * @throws Exception
     */
    public function processPipeline(array|string $pipelineName = '*'): mixed
    {
        // Process all pipelines
        if ($pipelineName == '*' || $pipelineName == 'all') {
            $pipelineName = cache('queue')->keys(env('APP_NAME') . '_' . '*');
        }

        // Handle array of pipeline names
        if (is_array($pipelineName)) {

            $jobStatus = [];

            foreach ($pipelineName as $pipeline) {

                $jobStatus[$pipeline] = true;

                // Add a mechanism to check if the pipeline is locked
                if ($this->isPipelineLocked($pipeline)) {
                    continue;
                }

                $jobStatus[$pipeline] = $this->processSinglePipeline($pipeline);
            }

            return $jobStatus;
        }

        // When working with coroutine, the pipeline name could be null
        if (is_null($pipelineName)) {
            return false;
        }

        if ($this->isPipelineLocked($pipelineName)) {
            return true;
        }

        // Handle single pipeline
        return $this->processSinglePipeline($pipelineName);
    }

    /**
     * Checks if a pipeline is locked.
     *
     * @param string $pipelineName
     * @return bool
     */
    private function isPipelineLocked(string $pipelineName): bool
    {
        return cache('queue')->get(Queue::LOCKED_STATE . ":{$pipelineName}") !== null;
    }

    /**
     * Processes a single pipeline.
     *
     * @param string $pipelineName
     * @return ?bool
     * @throws Exception
     */
    private function processSinglePipeline(string $pipelineName): ?bool
    {
        $time = time();

        $this->parsePipelineName($pipelineName);

        // Locks pipeline, this is "in progress" state,
        // this will avoid other workers take over it
        if ($this->setState($pipelineName, Queue::LOCKED_STATE)) {

            // Get all jobs from the pipeline and remove each
            while (true) {

                if (! $job = $this->pop($pipelineName)) {
                    break;
                }

                // Check if the job is locked
                if ($this->isJobLocked($job->uuid)) {
                    continue;
                }

                // Lock current job (in progress)
                if ($this->setState($job->uuid, Queue::LOCKED_STATE)) {

                    $jobName = get_class($job);

                    // If succeed...
                    if ($jobStatus = $job->doWork()) {
                        $this->setState(
                            "{$pipelineName}:job:{$jobName}:job_uuid:{$job->uuid}:at:" . $time,
                            Queue::COMPLETED_STATE
                        );
                    }

                    // If failed: put job back into the pipeline, delete lock state
                    // and makes it available for worker
                    if (! $jobStatus) {
                        cache('queue')->rpush($pipelineName, $job);
                        $this->setState(
                            "{$pipelineName}:job:{$job->uuid}",
                            Queue::FAILED_STATE
                        );
                    }

                    // Delete lock state from job. Applies to both states
                    $this->delState($job->uuid, Queue::LOCKED_STATE);
                }
            }

            $this->delState($pipelineName, Queue::LOCKED_STATE);

            return true;
        }

        return null;
    }

    /**
     * Checks if a job is locked.
     *
     * @param string $jobUuid
     * @return bool
     */
    private function isJobLocked(string $jobUuid): bool
    {
        return cache('queue')->get(Queue::LOCKED_STATE . ":{$jobUuid}") !== null;
    }

    /**
     * Gets a list of job UUIDs and their timestamps.
     *
     * @param string $pipelineName
     * @param string $state
     * @return array
     */
    public function getJobStatus(string $pipelineName = '*', string $state = Queue::COMPLETED_STATE): array
    {
        $this->parsePipelineName($pipelineName);

        $jobStatus  = [];

        foreach (cache('queue')->keys($state . ":{$pipelineName}:*") as $job) {

            $job = unserialize($job);

            $jobStatus[] = [
                'uuid' => $job->uuid,
                'timestamp' => cache('queue')->get($job->at)
            ];
        }

        return $jobStatus;
    }

    /**
     * Deletes all job statuses from the cache based on a pipeline name and state.
     *
     * @param string $state
     * @param string $pipelineName
     * @return integer
     */
    public function clearJobStatus(string $state = '*', string $pipelineName = '*'): int
    {
        $this->parsePipelineName($pipelineName);

        foreach ($jobs = cache('queue')->keys($state . ":{$pipelineName}:*") as $job) {
            cache('queue')->del($job);
        }

        return count($jobs);
    }

    /**
     * Parses pipeline name.
     *
     * @param string $pipelineName
     * @return void
     */
    private function parsePipelineName(string &$pipelineName): void
    {
        $appName = env('APP_NAME') . '_';

        if (str_ends_with($pipelineName, $this::DEFAULT_PIPELINE)) {
            return;
        }

        // Set pipeline name
        $pipelineName = ($pipelineName == 'all' || $pipelineName == '*')
            ? $appName . $this::DEFAULT_PIPELINE
            : $appName . $pipelineName;
    }

    /**
     * Sets the job state, this could be "Locking" a pipeline to prevent other workers
     * to process it, "completed" or "failed".
     *
     * @param string $pipelineName
     * @param string $state
     * @param integer $lockTime
     * @return mixed
     */
    private function setState(string $pipelineName, string $state = Queue::LOCKED_STATE, int $lockTime = 10): mixed
    {
        switch ($state) {
            case Queue::LOCKED_STATE:
                return cache('queue')->set($state . ":{$pipelineName}", time(), 'ex', $lockTime, 'nx');
                break;
            case Queue::COMPLETED_STATE:
            case Queue::FAILED_STATE:
                return cache('queue')->set($state . ":{$pipelineName}", time());
                break;
        }
    }

    /**
     * Removes states from the pipeline|job.
     *
     * @param string $pipelinName
     * @param string $state
     * @return mixed
     */
    private function delState(string $pipelineName, string $state = Queue::LOCKED_STATE): mixed
    {
        return cache('queue')->del($state . ":{$pipelineName}");
    }
}
