<?php

/*
|----------------------------------------------
| Set up your DB connections here.
|
| This config file is to store persistent data.
|----------------------------------------------
|
*/

return [

    'default' => [
        'mysql'
    ],

    'connections' => [

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
            'port'     => env("DB_PORT"),
            'driver'   => 'mysql'
        ],

        /*
         * MSSQL connection.
         * 
         * Note: Make sure you have installed MSSQL driver.
         */
        'mssql-001' => [
            'server'   => 'host',
            'username' => 'username',
            'password' => 'password',
            'database' => 'database',
            'port'     => '1433',
            'driver'   => 'sqlsrv'
        ],

        /*
         * PostgreSQL connection.
         * 
         * Note: Make sure you have installed PostgreSQL driver.
         */
        'postgres-001' => [
            'server'   => 'host',
            'username' => 'username',
            'password' => 'password',
            'database' => 'database',
            'port'     => '5432',
            'driver'   => 'pgsql'
        ],

        /*
         * SQLite connection.
         */
        'sqlite-001' => [
            'server'   => app()->basedir . '/db',
            'username' => null,
            'password' => null,
            'database' => 'new-database.sql',
            'port'     => null,
            'driver'   => 'sqlite'
        ],
    ],

    /*
     * Build your own cluster setup.
     * 
     * List all the required drivers you want to use for your cluster.
     */
    'clusters' => [
        'west-001' => [
            'server-n001' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
            'server-w002' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
            'server-w003' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
        ],

        'north-001' => [
            'server-n001' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
            'server-n002' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
            'server-n003' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
            'server-n004' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
        ],

        'east-001' => [
            'server-e001' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
            'server-e002' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
        ],

        'south-001' => [
            'server-s001' => [
                'server'   => 'server',
                'username' => 'username',
                'password' => 'password',
                'database' => 'database',
                'port'     => 'port'
            ],
        ],
    ],
];
