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
        if (PHP_SAPI !== 'cli') {

            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        
            // Get domain
            $domain = $_SERVER['HTTP_HOST'];
        
            // Handle localhost
            if ($domain == 'localhost') {
                $domain = 'localhost.test'; 
            }
        
            // Remove subdomains if present
            $domainParts = explode('.', $domain);
        
            if (count($domainParts) > 2) {
                $domain = $domainParts[count($domainParts)-2] . '.' . $domainParts[count($domainParts)-1]; 
            }
        
            session_name($domain . '_cookie');
        
            // Get the cookie params from the server
            $cookieParams = session_get_cookie_params();
        
            session_set_cookie_params(
                $cookieParams['lifetime'],
                $cookieParams['path'],
                $domain, 
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        
            session_start();
        
            setlocale(LC_MONETARY, 'en_US.UTF-8');
        }
    }

    public function boot(): void
    {

    }
}
