<?php

/*
|----------------------------------------------
| Main worker for the application
|----------------------------------------------
|
| This worker will be executed in the background by Supervisor.
|
*/

require_once __DIR__ . '/../vendor/autoload.php';

// while (true) {
    // Bring main worker
    app()->worker->handle() . PHP_EOL;

    echo '################################' . PHP_EOL;

    usleep(800);
// }

// Make sure you exit the application with 1
exit(1);
