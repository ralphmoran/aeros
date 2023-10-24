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

    /**
     * Adds a Job or an array of Job objects to a pipeline in the queue system.
     *
     * @param array $jobs Array of Job objects. Example: ['job1' => Interfaces\JobInterface]
     * @param string $pipelineName
     * @return boolean
     */
    public function push(array $jobs, string $pipelineName = null): bool
    {
        if (is_null($pipelineName)) {
            $pipelineName = $this::DEFAULT_PIPELINE;
        }

        $pipelineName = env('APP_NAME') . '_' . $pipelineName;

        // Adds jobs to a pipeline
        cache()->pipeline(function ($pipe) use ($jobs, $pipelineName) 
        {
            foreach ($jobs as $jobName => $jobClass) {

                $pipe->lpush("{$pipelineName}:{$jobName}", serialize($jobClass));

                $message = sprintf('Added new job to pipeline "%s": %s', $pipelineName, serialize($jobClass));

                // app()->event
                //     ->addEventListener($pipelineName, new \Events\QueueEvent($message))
                //     ->emit($pipelineName);

                app()->logger->log($message, app()->basedir . '/logs/event.log');
            }
        });

        return true;
    }

    /**
     * Removes a Job from the given pipeline from queue system.
     *
     * @param string $pipelineName Example: "pipeline1:job1"
     * @return JobInterface
     * @throws Exception
     */
    public function pop(string $jobName): JobInterface
    {
        if (strpos($jobName, ':') === false) {
            $jobName = env('APP_NAME') . '_' . $this::DEFAULT_PIPELINE . ':' . $jobName;
        }

        if (! cache()->exists($jobName)) {
            throw new \Exception("ERROR[Queue] Job '{$jobName}' is not registered.");
        }

        // Get the first job
        $jobObject = cache()->lrange($jobName, 0, 0)[0];

        // Initiate commit, remove first job, and execute command
        cache()->multi();
        cache()->ltrim($jobName, 1, -1);
        cache()->exec();

        // Emit event
        app()->event
            ->emit($jobName, $jobObject);

        return unserialize($jobObject);
    }

    /**
     * Processes and execute all jobs from a pipeline.
     *
     * @param string $pipelineName
     * @return bool
     * @throws Exception
     */
    public function processPipeline(string $pipelineName = ''): bool
    {
        // Get the pipeline name
        $pipelineName = $pipelineName ?: env('APP_NAME') . '_' . $this::DEFAULT_PIPELINE;

        // Get all jobs from the pipeline and remove each
        foreach (cache()->keys("{$pipelineName}:*") as $pipelineNameAndJobName) {
            $this->pop($pipelineNameAndJobName)->doWork();
        }

        // Cnofirm that all all elements from the pipeline were removed
        // $this->flush($pipelineName);

        return true;
    }

    /**
     * Removes/deletes all elements from the pipeline.
     *
     * @param string $pipelineName
     * @return void
     */
    public function flush(string $pipelineName = '*')
    {
        cache()->multi();

        $iterator = null;

        while ($iterator = cache()->scan($iterator, "{$pipelineName}*")) {

            foreach ($iterator as $key) {
                cache()->pipeline(function($pipe) use ($key) {
                    $pipe->del($key);
                });
            }
        
        }
        
        cache()->exec();
    }
}
