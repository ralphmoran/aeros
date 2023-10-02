<?php

/*
|----------------------------------------------
| Session is for web environment only
|----------------------------------------------
|
*/

if (PHP_SAPI !== 'cli') {
    // Get TLD
    $domainParts = explode('.', $_SERVER['HTTP_HOST']);
    $rootDomain = $domainParts[1] . '.' . $domainParts[2];

    session_name($domainParts[1] . '_cookie'); 

    // Get the cookie params from the server: e.i.: HTTP or HTTPS
    $cookieParams = session_get_cookie_params();

    session_set_cookie_params(
        $cookieParams['lifetime'], 
        $cookieParams['path'], 
        '.' . $rootDomain, 
        $cookieParams['secure'], 
        $cookieParams['httponly']
    );

    session_start();

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    setlocale(LC_MONETARY, 'en_US.UTF-8');
}
