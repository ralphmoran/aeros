<?php

/*
|----------------------------------------------
| Main worker for the application
|----------------------------------------------
|
| This worker will be executed in the background by Supervisor.
|
| It will take care of running and processing all the registered pipelines and jobs.
|
*/

require_once __DIR__ . '/../vendor/autoload.php';

$mainWorker = app()->bootApplication()->worker;

while (true) {

    try {
        // It takes care of all the registered pipelines and processing their jobs.
        $mainWorker->handle();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

exit(1);
