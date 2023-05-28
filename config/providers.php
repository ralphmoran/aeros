<?php

/*
|----------------------------------------------
| List your service providers
|----------------------------------------------
|
| Make sure your service providers are sorted as you expect to work.
|
| These are executed with the FIFO inventory management.
|
*/

return [
    'RouteServiceProvider' => Providers\RouteServiceProvider::class,
    'DatabaseServiceProvider' => Providers\DatabaseServiceProvider::class,
    'EmailServiceProvider' => Providers\EmailServiceProvider::class,
];