<?php

namespace Aeros\App\Providers;

use Aeros\Src\Classes\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    /**
     * Processes all default middlewares upon current request.
     *
     * @return void
     */
    public function register(): void
    {
        $middlewares = config('app.middlewares.app');

        // For security reasons, there are middlewares that need to run ALWAYS
        if (empty($middlewares)) {
            throw new \Exception('ERROR[middleware] No middlewares were found.');
        }

        \Aeros\Src\Classes\Router::runMiddlewares($middlewares);
    }

    /**
     * Boots other logic after this provider is registered.
     *
     * @return void
     */
    public function boot(): void
    {

    }
}
