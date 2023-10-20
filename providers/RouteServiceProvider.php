<?php

namespace Providers;

use Classes\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Loads/registers all routes.
     *
     * @return void
     */
    public function register(): void
    {
        $path = app()->basedir . '/routes';
        
        $routes = scan($path);

        if (empty($routes)) {
            throw new \Exception("ERROR[route] There are no routes registered.");
        }

        foreach ($routes as $file) {
            require $path . '/' . $file;
        }
    }

    public function boot(): void
    {
        
    }
}
