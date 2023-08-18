<?php

namespace Classes;

use Classes\Route;
use Interfaces\MiddlewareInterface;

class Router
{
    /** @var array */
    private $routes = [];

    /** @var array */
    private static $methods = [
        'get',
        'post',
        'put',
        'patch',
        'delete',
    ];

    public function __construct() {}

    /**
     * Registers a new Route based on the static call of the requested method.
     *
     * @param string $requestedMethod
     * @param array $args
     * @throws BadMethodCallException
     * @return void
     */
    public static function __callStatic(string $requestedMethod, array $args)
    {
        if (in_array($requestedMethod, self::$methods)) {
            return app()->router->addRoute(
                strtoupper($requestedMethod), 
                new Route($args[0], $args[1]) // (URI, handler)
            );
        }

        throw new \BadMethodCallException(
            sprintf(
                "ERROR[request method] Unknown request method: 'Route::%s()'", 
                $requestedMethod
            )
        );
    }

    /**
     * Registers a Route object.
     *
     * @param string $method
     * @param Route $route
     * @return void|Router
     */
    public function addRoute(string $method, Route $route)
    {
        if (array_key_exists($method, $this->routes) && in_array($route, $this->routes[$method])) {
            return;
        }

        $this->routes[$method][] = $route;

        return $route;
    }

    /**
     * Destructs a route.
     *
     * @param Route $route
     * @return array
     */
    public function parseRoute(Route $route) : array
    {
        $route->path = preg_replace(
            '/{([^}]+)}/', 
            ':$1',
            preg_replace("/(:\/)/", '@/', $route->path)
        );

        $tokens = explode('/', $route->path);
        $params = [];

        foreach ($tokens as $index => $token) {
            if (strpos($token, ':') !== false) {
                $params[substr($token, 1)] = $index;
            }
        }

        return [
            'path'    => $route->path,
            'handler' => $route->handler,
            'params'  => $params
        ];
    }

    /**
     * Parses a URI and return its parts and the subdomain if there is any.
     *
     * @param string $uri
     * @return array
     */
    public function getUriParts(string $uri) : array
    {
        $uriParts = array_values(
            array_filter(
                explode('/', $uri)
            )
        );

        if (substr_count($_SERVER['SERVER_NAME'], '.') == 2) {
            array_unshift($uriParts, explode('.', $_SERVER['SERVER_NAME'])[0] . '@');
        }

        return $uriParts;
    }

    /**
     * Confirms if the URI and the REQUEST_METHOD matches any saved route.
     *
     * @param string $method
     * @param string $uri
     * @return bool|array
     */
    public function match($method, $uri)
    {
        $uriParts = $this->getUriParts($uri);

        foreach ($this->getRoutes(strtoupper($method)) as $route) {

            $parsedRoute = $this->parseRoute($route);

            $tokens      = array_filter(
                                explode('/', $route->path)
                            );

            if (count($tokens) !== count($uriParts)) {
                continue;
            }

            $params = [];

            foreach ($parsedRoute['params'] as $name => $index) {
                $params[$name] = $uriParts[$index];
            }

            foreach ($tokens as $i => $token) {
                if (strpos($token, ':') === false && $token !== $uriParts[$i]) {
                    return false;
                }
            }

            return [
                'route' => $route,
                'params' => $params
            ];
        }

        return false;
    }

    /**
     * Dispatches the content for the route (URI) and method (REQUEST_METHOD).
     *
     * @throws Exception
     * @return mixed
     */
    public function dispatch(): mixed
    {
        $routeInstance = $this->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        if (! $routeInstance) {
            throw new \Exception(
                sprintf(
                    "ERROR[route] Route '%s:%s' does not exist.",
                    $_SERVER['REQUEST_METHOD'],
                    $_SERVER['REQUEST_URI']
                )
            );
        }

        // Call middleware(s) for this route
        self::runMiddlewares($routeInstance['route']->getMiddlewares());

        return $routeInstance['route']
                ->handler()
                ->getContent();
    }

    /**
     * Returns current registered routes from Router.
     *
     * @param string $method
     * @return array
     */
    public function getRoutes(string $method = '') : array
    {
        $method = strtoupper($method);

        if (! empty($method) && ! array_key_exists($method, $this->routes)) {
            throw new \Exception("ERROR[route] Method '{$method}' is not registered.");
        }

        if (! empty($method) && array_key_exists($method, $this->routes)) {
            return $this->routes[$method];
        }

        return $this->routes;
    }

    /**
     * Runs statically middlewares
     *
     * @param array $middlewares
     * @return void
     */
    public static function runMiddlewares(array $middlewares)
    {
        // Call middleware(s) for this route
        foreach ($middlewares as $middleware) {
            if (! in_array(MiddlewareInterface::class, class_implements($middleware))) {
                throw new \Exception(
                    sprintf(
                        "ERROR[middleware] Middleware '%s' does not exist or is invalid.",
                        $middleware
                    )
                );
            }

            // Pass request and reponse instances to the middleware to be processed
            (new $middleware())(app()->request, app()->response);
        }
    }
}
