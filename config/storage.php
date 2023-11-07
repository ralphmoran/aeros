<?php

/*
|----------------------------------------------
| Set up your storage system here.
|
| This config file defines where your physical 
| files are going to be stored: 'local', 's3'
|----------------------------------------------
|
*/

return [
    /*
     * By default, this is the driver to be used
     */
    'default' => [
        'local'
    ],

    'drivers' => [
        /*
         * Local storage: it requires the path on the server 
         * where files are going to be stored
         */
        'local' => [
            'path' => app()->basedir,
        ],

        /*
         * Amazon S3 storage: This is the connection to Amazon S3 
         * where your files are going to be stored
         */
        's3' => [
            'server'   => env("DB_HOST"),
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'database' => env("DB_DATABASE"),
            'port'     => env("DB_PORT")
        ],
    ],

    /*
     * You can set up different storage drivers here 
     * for data replication or propagation.
     */
    'cluster' => [

    ],
];
