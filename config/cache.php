<?php

/*
|----------------------------------------------
| Set up your cache system here
|
| This config file is to store valatile data
|----------------------------------------------
|
*/

return [
    /*
     * By default, this is the driver to be used
     */
    'default' => [
        'redis' //=> config('cache.drivers.redis'),
    ],

    'drivers' => [
        /*
         * Redis connection
         */
        'redis' => [
            'server'   => env("REDIS_HOST"),
            'password' => env("REDIS_PASSWORD"),
            'protocol' => env("REDIS_PROTOCOL"),
            'port'     => env("REDIS_PORT")
        ],
        
        /*
         * Memcache connection
         */
        'memcache' => [
            'server'   => env("MEMCACHE_HOST"),
            'password' => env("MEMCACHE_PASSWORD"),
            'protocol' => env("MEMCACHE_PROTOCOL"),
            'port'     => env("MEMCACHE_PORT")
        ],

        /*
         * MySQL connection
         */
        'mysql' => [
            'server'   => env("DB_HOST"),
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'database' => env("DB_DATABASE"),
            'port'     => env("DB_PORT")
        ],
    ],

    /*
     * Build your own cluster setup
     */
    'cluster' => [

    ],
];
