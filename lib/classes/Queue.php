<?php

namespace Classes;

use Interfaces\JobInterface;

/**
 * Asynchronouns in real-time execution.
 * 
 * - Jobs are pushed to a named queue for instant execution by workers.
 * - Allows handling time-consuming tasks without blocking the application.
 * - Workloads can scale by adding more queue workers.
 */

class Queue
{
    /** @var string */
    const DEFAULT_PIPELINE = 'gx_pipelines';

    /** @var string */
    const LOCK_STATE = 'lock';

    /** @var string */
    const COMPLETE_STATE = 'completed';

    /** @var string */
    const FAILED_STATE = 'failed';

    /** @var string */
    const REQUEUED_STATE = 'requeued';

    protected ?string $lockKey = null;

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

            if (class_exists($jobObj) && in_array('Interfaces\\JobInterface', class_implements($jobObj))) {

                $jobObj = serialize(new $jobObj);

                cache()->rpush($pipelineName, $jobObj);

                logger(
                    sprintf('Added new job to pipeline "%s": %s', $pipelineName, $jobObj), 
                    app()->basedir . '/logs/event.log'
                );
            }
        }

        return true;
    }

    /**
     * Executes and removes a Job from the pipeline's head.
     *
     * @param string $pipelineName Example: "pipeline1"
     * @return JobInterface|bool
     * @throws Exception
     */
    public function pop(string $pipelineName = '*'): JobInterface|bool
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
        if ($this->setState($pipelineName, Queue::LOCK_STATE)) {

            // Get all jobs from the pipeline and remove each
            while (true) {

                if (! $job = $this->pop($pipelineName)) {
                    break;
                }

                $this->delState($pipelineName, Queue::LOCK_STATE);

                $job->doWork();
            }

            return true;
        }

        return null;
    }

    /**
     * Parses pipeline name.
     *
     * @param string $pipelineName
     * @return void
     */
    protected function parsePipelineName(string &$pipelineName): void
    {
        // Set pipeline name
        $pipelineName = ($pipelineName == 'all' || $pipelineName == '*') 
                        ? env('APP_NAME') . '_' . $this::DEFAULT_PIPELINE 
                        : env('APP_NAME') . '_' . $pipelineName;
    }

    /**
     * Sets the job state, this could be "Lock" a pipeline to prevent other workers 
     * to process it, "completed", "failed", or "requeued".
     *
     * @param string $pipelineName
     * @param string $state 
     * @param integer $lockTime
     * @return mixed
     */
    protected function setState(string $pipelineName, string $state = Queue::LOCK_STATE, int $lockTime = 10): mixed
    {
        switch ($state) {
            case Queue::LOCK_STATE:
                return cache()->set($state . ":{$pipelineName}", 1, 'ex', $lockTime, 'nx');
                break;
            case Queue::COMPLETE_STATE:
            case Queue::FAILED_STATE:
            case Queue::REQUEUED_STATE:
                return cache()->set($state . ":{$pipelineName}", 1);
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
    protected function delState(string $pipelineName, string $state = Queue::LOCK_STATE): mixed
    {
        return cache()->del($state . ":{$pipelineName}");
    }
}
