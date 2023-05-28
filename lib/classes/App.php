<?php

namespace Classes;

use Aws\Api\Service;
use Classes\ServiceContainer;

class App extends ServiceContainer
{
    public function __construct(
        /** @var string */
        public string $rootDir = ''
    ) { }

    /**
     * Runs the application bootstrapping.
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->bootServiceProviders();

            printf('%s', $this->router->dispatch());

        } catch (\Exception $e) {
            printf('Caught exception: %s',  $e->getMessage());
        }

        exit;
    }

    /**
     * Sets the APP basedir
     *
     * @param string $dir
     * @return ServiceContainer
     */
    public function setBaseDir(string $dir): ServiceContainer
    {
        $this->rootDir = $dir;

        return $this;
    }
}
