<?php

namespace Aeros\App\Providers;

use Aeros\Src\Classes\Router;
use Aeros\Src\Classes\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Loads/registers all routes.
     *
     * @return void
     */
    public function register(): void
    {
        // On Production
        if (in_array(env('APP_ENV'), ['production', 'staging']) && cache('memcached')->get('cached.routes')) {
            return;
        }

        // Only on web
        if (strpos(PHP_SAPI, 'cli') === false) {

            $tld = explode('.', $_SERVER['HTTP_HOST']);

            // Loads routes for subdomain
            if (count($tld) > 2 && $tld[0] != 'www' && Router::loadRequestedRoutes($tld[0])) {
                return;
            }

            // There is no subdomain
            if (count($tld) == 2) {

                // Get the URI
                $uri = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));

                if (! empty($uri) && Router::loadRequestedRoutes(reset($uri))) {
                    return;
                }

                // Default routes: web.php
                Router::loadRequestedRoutes();

                return;
            }
        }
    }

    public function boot(): void
    {
        
    }
}
