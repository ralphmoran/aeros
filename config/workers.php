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
    | Add your workers here
    |
    */

    'ExampleWorker' => Workers\ExampleWorker::class,
    'AnotherWorker' => Workers\AnotherWorker::class,

    // ...
];
