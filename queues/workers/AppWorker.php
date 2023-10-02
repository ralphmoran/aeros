<?php

namespace Workers;

use Classes\Worker;

/**
 * This is the main worker for the application.
 * 
 * WARNING: DO NOT TOUCH or EDIT
 */

class AppWorker extends Worker
{
    public function handle()
    {
        // Add logic to handle Jobs from Cache (Redis)
        // cache(); OR app()->cache;
        echo '[' . getmypid() . '] Bringing next available Job...' . __FILE__ . PHP_EOL;
    }
}
