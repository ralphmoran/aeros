<?php

namespace Aeros\Lib\Classes;

use Aeros\Lib\Classes\Job;

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
     * @param array $jobs Array of Jobs
     * @param string $pipelineName
     * @return boolean
     */
    public function push(array $jobs, string $pipelineName = '*'): bool
    {
        $this->parsePipelineName($pipelineName);

        // Adds jobs to a pipeline
        foreach ($jobs as $jobObj) {

            if (class_exists($jobObj) && is_subclass_of($jobObj, Job::class)) {

                $jobObj = serialize(new $jobObj);

                cache()->rpush($pipelineName, $jobObj);
            }
        }

        return true;
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
        // Return and remove a job from the pipeline, if there is any
        return unserialize(cache()->lpop($pipelineName));
    }

    /**
     * Processes and execute all jobs from a pipeline.
     *
     * @param string $pipelineName <''> Empty or no value it will process the default pipeline
     *                              <'all'|'*'> it will process all pipelines
     * @return ?bool
     * @throws Exception
     */
    public function processPipeline(string $pipelineName = '*'): ?bool
    {
        $this->parsePipelineName($pipelineName);

        // Locks pipeline, this is "in progress" state, 
        // this will avoid other workers take over it
        if ($this->setState($pipelineName, Queue::LOCKED_STATE)) {

            // Get all jobs from the pipeline and remove each
            while (true) {

                if (! $job = $this->pop($pipelineName)) {
                    break;
                }

                // Lock current job (in progress)
                if ($this->setState($job->uuid, Queue::LOCKED_STATE)) {

                    // If succeed...
                    if ($jobStatus = $job->doWork()) {
                        $this->setState("{$pipelineName}:job:{$job->uuid}", Queue::COMPLETED_STATE);
                    }

                    // If failed: put job back into the pipeline, delete lock state 
                    // and makes it available for worker
                    if (! $jobStatus) {
                        cache()->rpush($pipelineName, $job);
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
     * Gets a list of job UUIDs and their timestamps.
     *
     * @param string $pipelineName
     * @param string $state
     * @return array
     */
    public function getJobStatus( string $state = Queue::COMPLETED_STATE, string $pipelineName = '*'): array
    {
        $this->parsePipelineName($pipelineName);

        $jobStatus  = [];

        foreach (cache()->keys($state . ":{$pipelineName}:*") as $job) {
            $jobStatus[] = [
                'uuid' => $job, 
                'timestamp' => cache()->get($job)
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

        foreach ($jobs = cache()->keys($state . ":{$pipelineName}:*") as $job) {
            cache()->del($job);
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
        // Set pipeline name
        $pipelineName = ($pipelineName == 'all' || $pipelineName == '*') 
                        ? env('APP_NAME') . '_' . $this::DEFAULT_PIPELINE 
                        : env('APP_NAME') . '_' . $pipelineName;
    }

    /**
     * Sets the job state, this could be "Lock" a pipeline to prevent other workers 
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
                return cache()->set($state . ":{$pipelineName}", time(), 'ex', $lockTime, 'nx');
                break;
            case Queue::COMPLETED_STATE:
            case Queue::FAILED_STATE:
                return cache()->set($state . ":{$pipelineName}", time());
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
        return cache()->del($state . ":{$pipelineName}");
    }
}
