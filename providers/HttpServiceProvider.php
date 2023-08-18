<?php

namespace Providers;

use Classes\Router;
use Classes\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    /**
     * Processes all default middlewares upon current request.
     *
     * @return void
     */
    public function register()
    {
        $middlewares = include app()->rootDir . '/config/middlewares.php';

        // For security reasons, there are middlewares that need to run ALWAYS
        if (empty($middlewares)) {
            throw new \Exception('ERROR[provider] No middlewares were found.');
        }

        Router::runMiddlewares($middlewares);
    }
}
