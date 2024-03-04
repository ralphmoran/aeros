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
     * By default, this is the connection to be used
     */
    'default' => [
        'memcached'
    ],

    'connections' => [
        /*
         * Redis connection
         */
        'redis' => [
            'server'   => env("REDIS_HOST"),
            'port'     => env("REDIS_PORT"),
            'protocol' => env("REDIS_PROTOCOL"),
            'password' => env("REDIS_PASSWORD"),
            'driver'   => 'redis'
        ],
        
        /*
         * Memcached connection
         */
        'memcached' => [
            'server'   => env("MEMCACHED_HOST"),
            'port'     => env("MEMCACHED_PORT"),
            'user'     => env("MEMCACHED_USER"),
            'password' => env("MEMCACHED_PASSWORD"),
            'driver'   => 'memcached'
        ],

        /*
         * Local connection
         */
        'local' => [
            'driver'   => 'local'
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
