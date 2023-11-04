<?php

/*
|----------------------------------------------
| Set up your DB drivers here
|----------------------------------------------
|
*/

return [
    /*
     * By default, this is the driver to be used
     */
    'default' => [
        'mysqli'
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
         * MySQLi connection
         */
        'mysqli' => [
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