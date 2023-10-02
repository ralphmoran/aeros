<?php

/*
|----------------------------------------------
| List your workers
|----------------------------------------------
|
| These workers are called and executed by Supervisor.
|
*/

return [
    /*
    |----------------------------------------------
    | Main worker for the application
    |
    */

    'AppWorker' => Workers\AppWorker::class,

    /*
    |----------------------------------------------
    | Add your workers here
    |
    */

    'ExampleWorker' => Workers\ExampleWorker::class,

    // ...
];
