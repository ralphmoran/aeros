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
        # TODO: Add to cache by domain or any other unique hash 
        // to avoid collisions: see env('HTTP_DOMAIN')

        // On Production
        if (in_array(env('APP_ENV'), ['production', 'staging']) && cache()->exists('cached.routes')) {
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
            cache()->set('cached.routes', serialize(app()->router->getRoutes()));
        }
    }

    public function boot(): void
    {
        
    }
}
