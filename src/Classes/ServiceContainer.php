<?php

namespace Aeros\Src\Classes;

use Aeros\Src\Classes\Kernel;

class ServiceContainer extends Kernel
{
    /** @var string */
    public string $basedir = '';

    /** @var array */
    protected $services = [];

    /** @var array */
    protected $providers = [];

    /** @var boolean */
    public $isAppBooted = false;

    /**
     * Runs the application.
     *
     * @return void
     */
    public function run()
    {
        try {
            // Booting app
            $this->bootstrap();

            // If env is prouction and cache is enabled, return route content
            if (isEnv('production') && env('CACHE')) {
                if ($content = cache('local')->getContent(app()->basedir . '/logs/cache/' . Route::getRouteHash() . '.log')) {
                    printf('%s', response($content));

                    exit;
                }
            }

            $content = app()->router->dispatch();

            if (empty($content)) {
                throw new \TypeError("ERROR[route] No content found.");
            }

            // If env is prouction and cache is enabled, store route content
            if (isEnv('production') && env('CACHE')) {
                cache('local')->create(
                    app()->basedir . '/logs/cache/' . Route::getRouteHash() . '.log', 
                    $content
                );
            }

            printf('%s', response($content));

        } catch (\Throwable $e) {

            // Log errors only on production
            if (isEnv('production')) {
                logger(
                    sprintf(
                        'Caught %s: %s. %s:%d.', 
                        get_class($e),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    )
                );

                exit;
            }

            # TODO: Improve visual error handling
            // view('common.errors.codes', ['code' => $e->getCode()]);

            // For development env only
            printf(
                'Caught %s: %s. %s:%d.', 
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }

        exit;
    }

    /**
     * Bootstraps application.
     *
     * @return ServiceContainer
     */
    public function bootstrap(): ServiceContainer
    {
        return $this->bootApplication()
                ->registerProviders()
                ->bootProviders();
    }

    /**
     * Sets the APP basedir
     *
     * @param string $dir
     * @return ServiceContainer
     */
    public function setBaseDir(string $dir): ServiceContainer
    {
        $this->basedir = $dir;

        return $this;
    }

    /**
     * Boots main App.
     * 
     * Add here any setup that is required to take place before anything else.
     *
     * @return \Aeros\Src\Classes\ServiceContainer
     */
    public function bootApplication(): ServiceContainer
    {
        if ($this->isAppBooted) {
            return $this;
        }

        (new \Aeros\App\Providers\AppServiceProvider)->register();

        $this->isAppBooted = true;

        return $this;
    }

    /**
     * Registers service providers.
     * 
     * @return \Aeros\Src\Classes\ServiceContainer
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
     * @return \Aeros\Src\Classes\ServiceContainer
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

        $providers = (isMode('cli')) ? config('app.providers.cli') : config('app.providers.web');

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
    public function register(string $name, string|callable $service)
    {
        if (isset($this->services[$name])) {
            return;
        }

        // Alias of this service
        if (is_string($service)) {

            if (! class_exists($service)) {
                throw new \Exception("ERROR[service] '{$service}' was not found.");

                return;
            }

            $this->services[$name] = new $service;

            return;
        }

        // Register a callable
        $this->services[$name] = $service;
    }

    /**
     * Bootstraps singleton services.
     *
     * @param string $name
     * @param string|callable $service
     * @return void
     */
    public function singleton(string $name, string|callable $service)
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
    public function get(string $name)
    {
        if (! isset($this->services[$name])) {
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
    public function __get(string $name)
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
