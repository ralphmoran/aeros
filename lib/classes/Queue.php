<?php

namespace Classes;

/**
 * Asynchronouns in real-time execution.
 * 
 * - Jobs are pushed to a named queue for instant execution by workers.
 * - Allows handling time-consuming tasks without blocking the application.
 * - Workloads can scale by adding more queue workers.
 */

class Queue
{
    /**
     * Adds a Job or a pipeline to the queue system.
     *
     * @param Job|Pipeline $payload
     * @param string $queueName
     * @return boolean
     */
    public function push(Job|Pipeline $payload, string $queueName = '_gxjobs'): bool
    {
        $queueName .= env('APP_NAME');

        // Validate if $payload is a Job or a Pipeline

            // If Job:
                // If '$queueName' is empty, add job to pipeline named "_gxjobs"
                // If '$queueName' is given, search for the pipeline, 
                    // If exists, add job to that pipeline
                    // If not, add job to new pipeline

            // If Pipeline:
                // If '$queueName' is empty, add pipeline to generic pipeline named "_gxpipeline"
                // If '$queueName' is given, search for the pipeline, 
                    // If exists, add all jobs to that pipeline
                    // If not, add all jobs to new pipeline

        // Check if $queueName is already in cache, if so, 
        cache()->set($queueName, $payload);
        cache()->exists($queueName);

        $message = 'Added new job|pipeline: ' . $queueName . ' (' . json_encode($payload) . ')';

        app()->event
            ->addEventListener($queueName, new \Events\QueueEvent($message))
            ->emit($queueName, $message);

        return true;
    }

    /**
     * Removes a Job from the given pipeline from queue system.
     *
     * @param string $queueName
     * @return Job|Pipeline
     */
    public function pop(string $queueName): Job|Pipeline
    {
        app()->event
            ->emit(
                $queueName, 
                cache()->get($queueName)
            );

        return new \Classes\Job();
    }
}
