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
        'memcached'
    ],

    'drivers' => [
        /*
         * Redis connection
         */
        'redis' => [
            'server'   => env("REDIS_HOST"),
            'port'     => env("REDIS_PORT"),
            'protocol' => env("REDIS_PROTOCOL"),
            'password' => env("REDIS_PASSWORD"),
        ],
        
        /*
         * Memcached connection
         */
        'memcached' => [
            'server'   => env("MEMCACHED_HOST"),
            'port'     => env("MEMCACHED_PORT"),
            'user'     => env("MEMCACHED_USER"),
            'password' => env("MEMCACHED_PASSWORD"),
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
    'clusters' => [
        'cluster-001' => [            
            'server-001' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
        ],
        'cluster-002' => [            
            'server-002' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
        ],
        'cluster-003' => [            
            'server-003' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
        ],
    ],
];
