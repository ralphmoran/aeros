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
            $jobs = cache('redis')->lrange($pipelineName, $start, $start + $chunk - 1);

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

        return cache('redis')->rpush($pipelineName, $serializedJob);
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
        $job = cache('redis')->lpop($pipelineName);

        if (empty($job) || is_array($job)) {
            return false;
        }

        // Return and remove a job from the pipeline, if there is any
        return @unserialize($job);
    }

    /**
     * Processes and execute all jobs from a pipeline.
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
            $pipelineName = cache('redis')->keys(env('APP_NAME') . '_' . '*');
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
        return cache('redis')->get(Queue::LOCKED_STATE . ":{$pipelineName}") !== null;
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
                        $this->setState("{$pipelineName}:job:{$jobName}:job_uuid:{$job->uuid}:at:" . $time, Queue::COMPLETED_STATE);
                    }

                    // If failed: put job back into the pipeline, delete lock state 
                    // and makes it available for worker
                    if (! $jobStatus) {
                        cache('redis')->rpush($pipelineName, $job);
                        $this->setState("{$pipelineName}:job:{$job->uuid}", Queue::FAILED_STATE);
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
        return cache('redis')->get(Queue::LOCKED_STATE . ":{$jobUuid}") !== null;
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

        foreach (cache('redis')->keys($state . ":{$pipelineName}:*") as $job) {

            $job = unserialize($job);

            $jobStatus[] = [
                'uuid' => $job->uuid, 
                'timestamp' => cache('redis')->get($job->at)
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

        foreach ($jobs = cache('redis')->keys($state . ":{$pipelineName}:*") as $job) {
            cache('redis')->del($job);
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
                return cache('redis')->set($state . ":{$pipelineName}", time(), 'ex', $lockTime, 'nx');
                break;
            case Queue::COMPLETED_STATE:
            case Queue::FAILED_STATE:
                return cache('redis')->set($state . ":{$pipelineName}", time());
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
        return cache('redis')->del($state . ":{$pipelineName}");
    }
}
