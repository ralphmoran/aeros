<?php

namespace Workers;

use Classes\Worker;

/**
 * This is an example of how a worker can be ran by Supervisor
 */

class AnotherWorker extends Worker
{
    public function handle()
    {
        // Add your logic here
        echo '[' . getmypid() . '] From: '. __CLASS__ . '::' . __METHOD__ . PHP_EOL;
        sleep(rand(1, 5));
    }
}
