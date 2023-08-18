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
    public function register()
    {
        $path = app()->rootDir . '/routes';
        
        $routes = scan($path);

        if (empty($routes)) {
            throw new \Exception("ERROR[route] There are no routes registered.");
        }

        foreach ($routes as $file) {
            require $path . '/' . $file;
        }
    }
}
