<?php

/*
|----------------------------------------------
| Main worker for the application
|----------------------------------------------
|
| This worker will be executed in the CLI by Supervisor.
|
| By default, Supervisord will create 10 instances of the app worker. 
|
*/

require_once __DIR__ . '/../vendor/autoload.php';

while (true) {
    // Bring main worker
    app()->worker->handle();

    sleep(1);
}
