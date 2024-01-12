<?php

namespace Aeros\Lib\Classes;

use Aeros\Lib\Interfaces\MiddlewareInterface;

class Route extends Router
{
    /** @var string */
    const GET = 'get';

    /** @var string */
    const POST = 'post';

    /** @var string */
    const DELETE = 'delete';

    /** @var string */
    const PUT = 'put';

    /** @var string */
    const PATCH = 'patch';

    // /** @var string */
    // const HEAD = 'head';

    // /** @var string */
    // const CONNECT = 'connect';

    // /** @var string */
    // const TRACE = 'trace';

    /** @var string */
    public $subdomain = null;

    /** @var array */
    public $uriParts = [];

    /** @var array */
    public $params = [];

    /** @var array */
    public $middlewares = [];

    /** @var string */
    public $content = null;

    /**
     * Constructor
     *
     * @param string $path
     * @param string|callable $handler
     */
    public function __construct(
        /** @var string */
        public string $path,

        /** @var string|callable */
        public $handler = null
    ) { }

    /**
     * Registers one or multiple middlewares sequentially.
     *
     * @param string|array $middleware
     * @return Route
     */
    public function withMiddleware(string|array $middlewares): Route
    {
        if (is_string($middlewares) && $this->isMiddleware($middlewares)) {
            if (! in_array($middlewares, $this->middlewares)) {
                $this->middlewares[] = $middlewares;
            }
        }

        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                if (! in_array($middleware, $this->middlewares) && $this->isMiddleware($middleware)) {
                    $this->middlewares[] = $middleware;
                }
            }
        }

        return $this;
    }

    /**
     * Checks if $middleware is a valid Middleware class
     *
     * @param string $middleware
     * @return bool
     */
    public function isMiddleware(string $middleware): bool
    {
        return class_exists($middleware) && in_array(MiddlewareInterface::class, class_implements($middleware));
    }

    /**
     * Returns registered middlewares.
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Determines the type of handler should be called: closure|callable or Controller
     *
     * @return Route
     */
    public function handler(): Route
    {
        if (is_callable($this->handler)) {
            $this->content = ($this->handler)();
        }

        // Controller name
        if (is_string($this->handler)) {
            $this->callController($this->handler);
        }

        return $this;
    }

    /**
     * Returns the already processed content that corresponds to the request.
     *
     * @return void
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Makes the controller call and assign the controller result to the content property.
     *
     * @param string $controller
     * @throws \Exception
     * @return void
     */
    private function callController(string $controller)
    {
        [$controllerName, $method] = strpos($controller, '@') === false
                                    ? [$controller, 'index']
                                    : explode('@', $controller);

        $controllerName = "Aeros\\Controllers\\$controllerName";

        if (get_parent_class($controllerName) != \Aeros\Lib\Classes\Controller::class || ! class_exists($controllerName)) {
            throw new \Exception(
                sprintf('ERROR[Controller] There was a problem trying to validate controller \'%s\.', $controllerName)
            );
        }

        if (! method_exists($controllerName, $method)) {
            throw new \Exception(
                sprintf('ERROR[Controller] Method \'%s\'::\'%s\' does not exist.', $controllerName, $method)
            );
        }

        //  Getting the controller instance
        $controller = new $controllerName;

        // Dynamically assign parameters to controller method
        $reflectionMethod = new \ReflectionMethod($controller, $method);

        $arguments = [];

        foreach ($reflectionMethod->getParameters() as $param) {

            if (! isset($this->params[':' . $param->name])) {
                throw new \Exception(
                    sprintf('ERROR[Route] Parameter \'%s\' does not exist in route \'%s\'.', $param->name, $this->path)
                );
            }

            $arguments[] = $this->params[':' . $param->name];
        }

        $this->content = $reflectionMethod->invokeArgs($controller, $arguments);
    }
}
