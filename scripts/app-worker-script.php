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

app()->bootApplication()->worker->start();

exit(1);
