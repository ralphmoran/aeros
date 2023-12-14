<?php

namespace Providers;

use Classes\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Processes global session.
     * 
     * The session ONLY works on web servers.
     *
     * @return void
     */
    public function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        // Only on web request
        if (PHP_SAPI !== 'cli') {

            if (session_status() == PHP_SESSION_ACTIVE) {
                
            }

            if (session_status() == PHP_SESSION_DISABLED) {
                
            }

            if (session_status() == PHP_SESSION_NONE) {
                
            }

            session_start();

            // logger(json_encode(getallheaders()), app()->basedir . '/logs/error.log');
            // logger(json_encode(apache_request_headers()), app()->basedir . '/logs/error.log');
            // logger(json_encode(apache_response_headers()), app()->basedir . '/logs/error.log');

            // unset($_SESSION['counter']);

            // if (! array_key_exists(env('HTTP_DOMAIN'), $_COOKIE)) {

            //     // Get the cookie params from the server
            //     $cookieParams = session_get_cookie_params();

            //     $cookieDomain = str_replace('.', '_', env('HTTP_DOMAIN'));

            //     setCookieWith(
            //         $cookieDomain, 
            //         'greatAnother' . rand(1, 10), 
            //         time() + 60, 
            //         $cookieParams['path'], 
            //         env('HTTP_DOMAIN'),
            //         $cookieParams['secure'],
            //         $cookieParams['httponly']
            //     );
            // }

            // // Get domain
            // $domain = $_SERVER['HTTP_HOST'];

            // // Handle localhost
            // if ($domain == 'localhost') {
            //     $domain = 'localhost.test'; 
            // }

            // // // Remove subdomains if present
            // $domainParts = explode('.', $domain);

            // if (count($domainParts) > 2) {
            //     $domain = $domainParts[count($domainParts)-2] . '.' . $domainParts[count($domainParts)-1]; 
            // }

            // // dd($domain);

            // session_name($domain . '_cookie');

            // // Get the cookie params from the server
            // $cookieParams = session_get_cookie_params();

            // session_set_cookie_params(
            //     $cookieParams['lifetime'],
            //     $cookieParams['path'],
            //     $domain, 
            //     $cookieParams['secure'],
            //     $cookieParams['httponly']
            // );

            // session_start();
        }
    }

    public function boot(): void
    {

    }
}
