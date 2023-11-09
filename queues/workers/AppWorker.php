<?php

namespace Workers;

use Classes\Worker;

class AppWorker extends Worker
{
    /**
     * WARNING: DO NOT EDIT THIS FILE, UNLESS YOU KNOW WHAT YOU ARE DOING
     * 
     * This is the main worker for the application. It handles all the registered
     * pipelines (Jobs) in the queue systeme (Redis).
     *
     * @return void
     */
    public function handle()
    {
        app()->queue->processPipeline();
    }

    /**
     * Creates a new worker class, worker log file, and a worker conf file.
     *
     * @param string $scriptName Format: 'example-worker' => 'ExampleWorker' for worker class, 
     *                                   'example-worker' => 'example-worker-script' for worker script
     *                                   'example-worker' => 'example-worker-script' for worker conf file
     * @param integer $proccessNum
     * @return void
     */
    public function create(string $scriptName, int $proccessNum = 3)
    {
        // Give the proper format
        $workerName = implode('',
            array_map(
                'ucfirst', 
                explode('-', $scriptName)
            )
        );

        // Create worker class
        app()->file->createFromTemplate(
            env('WORKERS_DIR') . '/' . $workerName . '.php', 
            env('WORKERS_DIR') . '/TemplateWorker.template', 
            ['worker-name' => $workerName]
        );

        // Create worker script file
        app()->file->createFromTemplate(
            env('SCRIPTS_DIR') . '/' . $scriptName . '.php', 
            env('SCRIPTS_DIR') . '/template-worker-script.template', 
            ['worker-name' => $workerName,]
        );

        // Create config worker file
        app()->file->createFromTemplate(
            env('WORKERS_CONF_DIR') . '/' . $scriptName . '.conf', 
            env('WORKERS_CONF_DIR') . '/conf.template', 
            [
                'script-name' => $scriptName,
                'process-num' => $proccessNum,
            ]
        );

        // Create log file for new worker
        app()->file->create(env('LOGS_DIR') . '/' . $scriptName . '.log');

        return $this;
    }

    /**
     * Calls the requested worker and puts it to do its job.
     *
     * @param string $workerName
     * @return void
     */
    public function call(string $worker)
    {
        if (! $this->isWorkerValid($worker)) {

            throw new \Exception(
                sprintf('ERROR[Worker] There was a problem validating worker \'%s\.', $worker)
            );

        }

        (new $worker)->handle();
    }

    /**
     * Calls all the registered workers in ./config/workers.php.
     *
     * @return void
     */
    public function callAll(array $workers = [])
    {
        $workers = ! empty($workers) ? $workers : config('workers');

        if (is_array($workers) && ! empty($workers)) {

            foreach ($workers as $worker) {

                if (! $this->isWorkerValid($worker)) {

                    throw new \Exception(
                        sprintf('ERROR[Worker] There was a problem validating worker \'%s\.', $worker)
                    );

                    continue;
                }

                // Run the worker handle method
                (new $worker)->handle();
            }
        }
    }

    /**
     * Checks if a worker is valid.
     *
     * @param string $worker
     * @return boolean
     */
    private function isWorkerValid(string $worker): bool
    {
        return (! class_exists($worker) || get_parent_class($worker) != Worker::class);
    }
}
