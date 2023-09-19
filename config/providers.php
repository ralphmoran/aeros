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
    'MimeTypesServiceProvider' => Providers\MimeTypesServiceProvider::class,
    'HttpServiceProvider' => Providers\HttpServiceProvider::class,
    'RouteServiceProvider' => Providers\RouteServiceProvider::class,
    'DatabaseServiceProvider' => Providers\DatabaseServiceProvider::class,
    'EmailServiceProvider' => Providers\EmailServiceProvider::class,
];
