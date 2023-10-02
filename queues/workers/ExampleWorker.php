<?php

namespace Workers;

use Classes\Worker;

/**
 * This is an example of how a worker can be ran by Supervisor
 */

class ExampleWorker extends Worker
{
    public function handle()
    {
        // Add your logic here
        echo 'From: '. __FILE__ . PHP_EOL;
    }
}
