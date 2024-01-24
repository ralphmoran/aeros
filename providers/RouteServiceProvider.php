<?php

namespace Aeros\Providers;

use Aeros\Lib\Classes\ServiceProvider;

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

        // On development
        $path = app()->basedir . '/routes';
        
        $routes = scan($path);

        if (empty($routes)) {
            throw new \Exception("ERROR[route] There are no routes registered.");
        }

        foreach ($routes as $file) {
            require $path . '/' . $file;
        }

        // Caching routes for production|staging
        if (in_array(env('APP_ENV'), ['production', 'staging'])) {
            cache('memcached')->set('cached.routes', app()->router->getRoutes());
        }
    }

    public function boot(): void
    {
        
    }
}
