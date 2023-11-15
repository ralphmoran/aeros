<?php

/*
|----------------------------------------------
| Application setup
|----------------------------------------------
|
| All regarding your application setup goes here.
|
*/

return [

    /*
    |----------------------------------------------
    | Views setup.
    */

    'views' => [
        'basepath' => app()->basedir . '/views'
    ],

    /*
    |----------------------------------------------
    | Group of providers.
    */

    'providers' => [

        // Main service providers: DO NOT TOUCH
        'SessionServiceProvider' => Providers\SessionServiceProvider::class,
        'MimeTypesServiceProvider' => Providers\MimeTypesServiceProvider::class,
        'HttpServiceProvider' => Providers\HttpServiceProvider::class,
        'RouteServiceProvider' => Providers\RouteServiceProvider::class,

        // Other service providers
        'DatabaseServiceProvider' => Providers\DatabaseServiceProvider::class,
        'EmailServiceProvider' => Providers\EmailServiceProvider::class,
    ],

    /*
    |----------------------------------------------
    | Group of middlewares. These can be applied by 
    | just calling the group name.
    */

    'middlewares' => [

        // Run over any request
        'app' => [
            'SessionMiddleware' => Middlewares\SessionMiddleware::class,
            'CorsMiddleware' => Middlewares\CorsMiddleware::class,
            'BanBotsMiddleware' => Middlewares\BanBotsMiddleware::class,
            'SanitizerMiddleware' => Middlewares\SanitizerMiddleware::class,
        ],

        'web' => [

        ],

        'api' => [

        ],

        'auth' => [

        ],
    ],
];
