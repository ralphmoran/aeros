<?php

/*
|----------------------------------------------
| Session is for web environment only
|----------------------------------------------
|
*/

return [

    /*
    |----------------------------------------------
    | Session headers.
    */
    'headers' => [

        'default' => [
            'Content-type'  => 'text/html;charset=UTF-8',
            'Date'          => gmdate('D, d M Y H:i:s', strtotime('now')) . ' ' . date('T'),
            'Expires'       => gmdate('D, d M Y H:i:s', strtotime('+1 week')) . ' ' . date('T'),
            'Cache-Control' => 'max-age=3600', // no-store: Do not cache. private: Caches only on local, e.g. browser.
        ],

        // CORS
        'cors' => [
            'Access-Control-Allow-Origin'      => (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : $_SERVER['HTTP_HOST']),
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE',
            'Access-Control-Allow-Headers'     => 'Origin, Authorization, Content-type',
            'Access-Control-Max-Age'           => '3600',
        ]
    ],

    /*
    |----------------------------------------------
    | Special cookie session options.
    */
    'options' => [
        'read_and_close' => false, // If true, nothing will be stored in session
    ],

    /*
    |----------------------------------------------
    | Cookie setup.
    */
    'cookie' => [
        'cookie_name' => env('APP_NAME') . '_phpsessid',
        'lifetime' => 0, // 0: Never expires
        'path' => '/',
        'domain' => env('HTTP_DOMAIN'),
        'secure' => false,
        'httponly' => true,
        'samesite' => '', // 'None', 'Strict' or 'Lax'. To use these, it's required to set 'secure' => true (HTTPS)
    ],
];
