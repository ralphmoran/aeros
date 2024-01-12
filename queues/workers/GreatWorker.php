<?php

namespace Aeros\Queues\Workers;

use Aeros\Lib\Classes\Worker;

/**
 * This worker will be ran by Supervisor on the background thread.
 * 
 * Make sure to run `composer update-workers`. 
 * 
 * WARNING: This command will stop any running worker.
 */

class GreatWorker extends Worker
{
    public function handle()
    {
        // Add your logic here
        // ...
    }
}
