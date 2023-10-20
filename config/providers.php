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
    /*
    |----------------------------------------------
    | Main service providers: DO NOT TOUCH
    */

    'SessionServiceProvider' => Providers\SessionServiceProvider::class,
    'MimeTypesServiceProvider' => Providers\MimeTypesServiceProvider::class,
    'HttpServiceProvider' => Providers\HttpServiceProvider::class,
    'RouteServiceProvider' => Providers\RouteServiceProvider::class,

    /*
    |----------------------------------------------
    | Other service providers
    */

    'DatabaseServiceProvider' => Providers\DatabaseServiceProvider::class,
    'EmailServiceProvider' => Providers\EmailServiceProvider::class,
];
