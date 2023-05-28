<?php

return [
    /**
     * MySQLi connection
     */
    'mysqli' => [
        'server'   => env("DB_SERVER"),
        'username' => env("DB_USERNAME"),
        'password' => env("DB_PASSWORD"),
        'database' => env("DB_DATABASE"),
        'port'     => env("DB_PORT")
    ],

    /**
     * MySQLi (read only) connection
     */
    'mysqli_ro' => [
        'server'   => env("RO_DB_SERVER"),
        'username' => env("RO_DB_USERNAME"),
        'password' => env("RO_DB_PASSWORD"),
        'database' => env("RO_DB_DATABASE"),
        'port'     => env("RO_DB_PORT")
    ],

    /**
     * Redis connection
     */
    'redis' => [
        'server'   => '',
        'username' => '',
        'password' => '',
        'database' => '',
        'port'     => ''
    ],
];