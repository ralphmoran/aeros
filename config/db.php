<?php

/*
|----------------------------------------------
| Set up your DB drivers here.
|
| This config file is to store persistent data
|----------------------------------------------
|
*/

return [

    'default' => [
        'mysql'
    ],

    'drivers' => [
        /*
         * Redis connection.
         * 
         * Note: Make sure you have installed Redis server.
         */
        'redis' => [
            'server'   => env("REDIS_HOST"),
            'password' => env("REDIS_PASSWORD"),
            'protocol' => env("REDIS_PROTOCOL"),
            'port'     => env("REDIS_PORT")
        ],

        /*
         * MySQL connection.
         * 
         * Note: Make sure you have installed MySQL driver.
         */
        'mysql' => [
            'server'   => env("DB_HOST"),
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'database' => env("DB_DATABASE"),
            'port'     => env("DB_PORT")
        ],

        /*
         * PostgreSQL connection.
         * 
         * Note: Make sure you have installed PostgreSQL driver.
         */
        'postgres' => [
            'server'   => 'host',
            'username' => 'username',
            'password' => 'password',
            'database' => 'database',
            'port'     => '5432'
        ],

        /*
         * SQLite connection.
         */
        'sqlite' => [
            'server'   => app()->basedir . '/db',
            'database' => 'database'
        ],
    ],

    /*
     * Build your own cluster setup.
     * 
     * List all the required drivers you want to use for your cluster.
     */
    'cluster' => [

    ],
];
