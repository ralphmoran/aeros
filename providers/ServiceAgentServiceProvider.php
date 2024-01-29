<?php

namespace Aeros\Providers;

use Aeros\Lib\Classes\ServiceProvider;

/**
 * This class acts as a service agent that handles other service provider boot method.
 */
class ServiceAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
        // If you require to register a service into the service container
        //
    }

    public function boot(): void
    {
        //
        // If you require to boot a service after all services were registered
        //
    }

    /**
     * It makes the call to the service provider from resgistered ServiceAgentServiceProvider
     * or by app()->service->call($serviceProvider);
     *
     * @param ServiceProvider $serviceProvider
     * @return void
     */
    public function call(ServiceProvider $serviceProvider): void
    {
        $serviceProvider->boot();
    }
}
