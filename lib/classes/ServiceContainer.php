<?php

namespace Classes;

use Classes\Kernel;

class ServiceContainer extends Kernel
{
    /** @var array */
    protected $services = [];

    /** @var array */
    protected $providers = [];

    /**
     * Registers service providers.
     * 
     * @return Classes\ServiceContainer
     */
    public function registerProviders(): ServiceContainer
    {
        foreach ($this->getProviders() as $providerWithNamespace) {
            if ($this->isProvider($providerWithNamespace)) {
                (new $providerWithNamespace)->register();
            }
        }

        return $this;
    }

    /**
     * Boots all already registered service providers.
     *
     * @return Classes\ServiceContainer
     */
    public function bootProviders(): ServiceContainer
    {
        foreach ($this->getProviders() as $providerWithNamespace) {
            if ($this->isProvider($providerWithNamespace)) {
                (new $providerWithNamespace)->boot();
            }
        }

        return $this;
    }

    /**
     * Gets the current registered providers from /config/providers.php.
     *
     * @return array
     */
    public function getProviders() : array
    {
        if (! empty($this->providers)) {
            return $this->providers;
        }

        $providers = config('providers');

        if (empty($providers)) {
            throw new \Exception('ERROR[provider] No providers were found.');
        }

        return $this->providers = $providers;
    }

    /**
     * Registers a service or a definition.
     *
     * @param string $name
     * @param string|callable $service
     * @return void
     */
    public function register(string $name, $service)
    {
        if (array_key_exists($name, $this->services)) {
            return;
        }

        // Alias of this service
        if (is_string($service)) {

            if (! class_exists($service)) {
                throw new \Exception("ERROR[service]'{$service}' was not found.");

                return;
            }

            $this->services[$name] = new $service;

            return;
        }

        $this->services[$name] = $service;
    }

    /**
     * Bootstraps singleton services.
     *
     * @param array|string $name
     * @param string|callable $service
     * @return void
     */
    public function singleton($name, string|callable $service)
    {
        if (is_string($service) || is_callable($service)) {
            $this->register($name, $service);

            return;
        }

        throw new \Exception("ERROR[singleton]'{$service}' is not a valid data type.");
    }

    /**
     * Gets the service or definition if it's already registered.
     *
     * @param string $name
     * @return object|callable
     */
    public function get($name)
    {
        if (! array_key_exists($name, $this->services)) {
            throw new \Exception("Service '$name' is not registered.");
        }

        return $this->services[$name];
    }

    /**
     * Retrieves magically the service or callable.
     *
     * @param string $name
     * @return object|callable
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Returns the array of registered services.
     *
     * @return array
     */
    public function getServices() : array
    {
        return $this->services;
    }

    /**
     * Validates if a service provider.
     *
     * @param string $providerWithNamespace
     * @return boolean
     */
    private function isProvider(string $providerWithNamespace): bool
    {
        if (! class_exists($providerWithNamespace) || ! is_subclass_of($providerWithNamespace, ServiceProvider::class)) {
            throw new \Exception(
                sprintf('ERROR[provider] Provider "%s" were not found or invalid.', $providerWithNamespace)
            );
        }

        return true;
    }
}
