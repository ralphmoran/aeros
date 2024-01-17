<?php

/*
|----------------------------------------------
| GreatWorker worker
|----------------------------------------------
|
| This worker will be executed in the background by Supervisor.
|
*/

// If you require to load Aeros framework, comment this line in.
// require_once __DIR__ . '/../vendor/autoload.php';

/**
 * All workers, in order to run indefinitely, need to run an infinite loop.
 */
// while (true) {

    // Add here the logic you need to run in the background.
    echo '[' . date('Y-m-d H:i:s') . '][' . getmypid() . '] From: '. __FILE__ . PHP_EOL;
// }

// Make sure you exit the application with 1
exit(1);
