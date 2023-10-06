<?php

namespace Workers;

use Classes\Worker;

/**
 * This is the main worker for the application.
 */

class AppWorker extends Worker
{
    public function handle()
    {
        echo 'From: ' . __CLASS__ . '::' . __FILE__ . PHP_EOL;
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
        app()->file->create(env('LOGS_DIR'). '/' . $scriptName . '.log');

        return $this;
    }

    public function worker(string $workerName)
    {
        // if (! class_exists($worker) || get_parent_class($worker) != 'Classes\\Worker') {
        //     throw new \Exception(
        //             sprintf('ERROR[Worker] There was a problem trying to validate worker \'%s\.', $worker)
        //         );
        // }

        // // Run the worker handle method
        // (new $worker)->handle();

        // Add logic to handle Jobs from Cache (Redis)
        // cache(); OR app()->cache;
        // $workers = require_once app()->rootDir . '/config/workers.php';

        // if (is_array($workers) && ! empty($workers)) {

        //     foreach ($workers as $worker) {

        //         if (! class_exists($worker) || get_parent_class($worker) != 'Classes\\Worker') {
        //             throw new \Exception(
        //                     sprintf('ERROR[Worker] There was a problem trying to validate worker \'%s\.', $worker)
        //                 );
        //         }

        //         // Run the worker handle method
        //         (new $worker)->handle();
        //     }
        // }
    }

    private function isValidWorker()
    {
        
    }
}
