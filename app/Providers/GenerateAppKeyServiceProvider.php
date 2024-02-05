<?php

namespace Aeros\App\Providers;

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
            $envFile = app()->basedir . '/../.env';
            $newAppKey = bin2hex(random_bytes(32));

            $envBody = preg_replace(
                '/(APP_KEY=)(.*)/', 
                'APP_KEY=' . $newAppKey, 
                file_get_contents($envFile)
            );

            file_put_contents($envFile, $envBody);
        }
    }
}
