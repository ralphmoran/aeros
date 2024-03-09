<?php

namespace App\Providers;

use Aeros\Src\Classes\ServiceProvider;

class GenerateAppKeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
        // If you require to register a service into the service container
        //
    }

    public function boot(): void
    {
        // Generate APP_KEY: env('APP_KEY')
        if (empty(env('APP_KEY'))) {
            updateEnvVariable(['APP_KEY' => bin2hex(random_bytes(32))]);
        }
    }
}
