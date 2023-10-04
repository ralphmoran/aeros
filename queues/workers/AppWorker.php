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
        // Add logic to handle Jobs from Cache (Redis)
        // cache(); OR app()->cache;
        $workers = require_once app()->rootDir . '/config/workers.php';

        if (is_array($workers) && ! empty($workers)) {

            foreach ($workers as $worker) {

                if (! class_exists($worker) || get_parent_class($worker) != 'Classes\\Worker') {
                    throw new \Exception(
                            sprintf('ERROR[Worker] There was a problem trying to validate worker \'%s\.', $worker)
                        );
                }

                // Run the worker handle method
                (new $worker)->handle();
            }
        }
    }
}
