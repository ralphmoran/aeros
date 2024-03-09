<?php

namespace App\Providers;

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
        if (isEnv('production')) {
            return;
        }

        Router::loadRequestedRoutes();
    }

    public function boot(): void
    {
        
    }
}
