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
            'SessionServiceProvider' => \Aeros\App\Providers\SessionServiceProvider::class,
            'HttpServiceProvider' => \Aeros\App\Providers\HttpServiceProvider::class,
            'RouteServiceProvider' => \Aeros\App\Providers\RouteServiceProvider::class,

            // Other service providers
            'DatabaseServiceProvider' => \Aeros\App\Providers\DatabaseServiceProvider::class,
            'EmailServiceProvider' => \Aeros\App\Providers\EmailServiceProvider::class,
        ],

        // Other service providers that run on CLI
        'cli' => [
            'RouteServiceProvider' => \Aeros\App\Providers\RouteServiceProvider::class,
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
            'SessionMiddleware' => \Aeros\App\Middlewares\SessionMiddleware::class,
            'CorsMiddleware' => \Aeros\App\Middlewares\CorsMiddleware::class,
            'BanBotsMiddleware' => \Aeros\App\Middlewares\BanBotsMiddleware::class,
            'SanitizerMiddleware' => \Aeros\App\Middlewares\SanitizerMiddleware::class,
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
    |    - \Aeros\App\Providers\ServiceProvider::class
    |    - \Aeros\Src\Classes\Worker::class
    |    - \Aeros\Src\Classes\Cron::class
    |    - \Aeros\Src\Classes\Job::class
    |    - \Aeros\Src\Classes\Observable::class
    */
    'warmup' => [
        'GenerateAppKeyServiceProvider' => \Aeros\App\Providers\GenerateAppKeyServiceProvider::class,
        'MimeTypeServiceProvider' => \Aeros\App\Providers\MimeTypeServiceProvider::class,
        'CacheRoutesServiceProvider' => \Aeros\App\Providers\CacheRoutesServiceProvider::class,
    ],
];
