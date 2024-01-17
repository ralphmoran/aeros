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

        // Providers that run only on web servers.
        'web' => [
            // Main service providers: DO NOT TOUCH
            'SessionServiceProvider' => \Aeros\Providers\SessionServiceProvider::class,
            'HttpServiceProvider' => \Aeros\Providers\HttpServiceProvider::class,
            'RouteServiceProvider' => \Aeros\Providers\RouteServiceProvider::class,

            // Other service providers
            'DatabaseServiceProvider' => \Aeros\Providers\DatabaseServiceProvider::class,
            'EmailServiceProvider' => \Aeros\Providers\EmailServiceProvider::class,
        ],

        // Other service providers that run on CLI
        'cli' => [
            'RouteServiceProvider' => \Aeros\Providers\RouteServiceProvider::class,
        ],
    ],

    /*
    |----------------------------------------------
    | Group of middlewares. These can be applied by 
    | just calling the group name.
    */
    'middlewares' => [

        // Run over any request
        'app' => [
            'SessionMiddleware' => \Aeros\Middlewares\SessionMiddleware::class,
            'CorsMiddleware' => \Aeros\Middlewares\CorsMiddleware::class,
            'BanBotsMiddleware' => \Aeros\Middlewares\BanBotsMiddleware::class,
            'SanitizerMiddleware' => \Aeros\Middlewares\SanitizerMiddleware::class,
        ],

        'web' => [

        ],

        'api' => [

        ],

        'auth' => [

        ],
    ],

    /*
    |----------------------------------------------
    | User roles. It can be listed and implemented.
    */
    'users' => [
        'roles' => [
            // 'super' => Roles\SuperRole::class,
            // 'admin' => Roles\AdminRole::class,
            // 'guest' => Roles\GuestRole::class,
        ]
    ],

    /*
    |----------------------------------------------
    | Add any process that you require to warm up 
    | the application in general.
    |
    | Supported instances:
    |    - \Aeros\Providers\ServiceProvider::class
    |    - \Aeros\Lib\Classes\Worker::class
    |    - \Aeros\Lib\Classes\Cron::class
    |    - \Aeros\Lib\Classes\Job::class
    |    - \Aeros\Lib\Classes\Observable::class
    */
    'warmup' => [
        'MimeTypeServiceProvider' => \Aeros\Providers\MimeTypeServiceProvider::class,
    ],
];
