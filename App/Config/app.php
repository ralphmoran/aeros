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
    | Timezone.
    */
    'timezone' => 'America/Los_Angeles',

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
            'SessionServiceProvider' => \App\Providers\SessionServiceProvider::class,
            'CsrfTokenServiceProvider' => \App\Providers\CsrfTokenServiceProvider::class,
            'HttpServiceProvider' => \App\Providers\HttpServiceProvider::class,
            'RouteServiceProvider' => \App\Providers\RouteServiceProvider::class,
        ],

        // Other service providers that run on CLI
        'cli' => [
            'RouteServiceProvider' => \App\Providers\RouteServiceProvider::class,
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
            'SessionMiddleware' => \App\Middlewares\SessionMiddleware::class,
            'BanBotsMiddleware' => \App\Middlewares\BanBotsMiddleware::class,
            'SanitizerMiddleware' => \App\Middlewares\SanitizerMiddleware::class,
            'VerifyCsrfTokenMiddleware' => \App\Middlewares\VerifyCsrfTokenMiddleware::class,
        ],

        'web' => [

        ],

        'api' => [
            'CorsMiddleware' => \App\Middlewares\CorsMiddleware::class,
        ],

        'auth' => [

        ],

        // 'another' => [
        //     'AnotherMiddleware' => \App\Middlewares\AnotherMiddleware::class,
        //     ...
        // ],
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
    |    - \App\Providers\ServiceProvider::class
    |    - \Aeros\Src\Classes\Worker::class
    |    - \Aeros\Src\Classes\Cron::class
    |    - \Aeros\Src\Classes\Job::class
    |    - \Aeros\Src\Classes\Observable::class
    */
    'warmup' => [
        'GenerateAppKeyServiceProvider' => \App\Providers\GenerateAppKeyServiceProvider::class,
        'MimeTypeServiceProvider' => \App\Providers\MimeTypeServiceProvider::class,
        'CacheRoutesServiceProvider' => \App\Providers\CacheRoutesServiceProvider::class,
        'AppEventListernersServiceProvider' => \App\Providers\AppEventListernersServiceProvider::class,
    ],
];
