<?php

namespace App\Providers;

use Aeros\Src\Classes\ServiceProvider;

/**
 * CacheRoutesServiceProvider
 *
 * This class extends the base ServiceProvider class and is responsible for caching routes.
 *
 * @package App\Providers
 */
class CacheRoutesServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Load and cache all routes
        if (load(app()->basedir . '/routes')) {
            cache('memcached')->set('cached.routes', app()->router->getRoutes());
        }
    }
}
