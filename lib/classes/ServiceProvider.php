<?php

namespace Aeros\Lib\Classes;

abstract class ServiceProvider
{
    /**
     * It registers a service provider in the service container.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * This method is called after all service providers are registered.
     *
     * @return void
     */
    abstract public function boot(): void;
}
