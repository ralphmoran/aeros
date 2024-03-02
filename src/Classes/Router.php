<?php

namespace Aeros\Src\Classes;

use Aeros\Src\Classes\Route;
use Aeros\Src\Interfaces\MiddlewareInterface;

/**
 * Router class manages static calls to HTTP methods, parses the registered routes and
 * parses the current requested URI.
 * 
 * @method static \Aeros\Src\Classes\Route get(string $route, callable|string $handler)
 * @method static \Aeros\Src\Classes\Route post(string $route, callable|string $handler)
 * @method static \Aeros\Src\Classes\Route put(string $route, callable|string $handler)
 * @method static \Aeros\Src\Classes\Route path(string $route, callable|string $handler)
 * @method static \Aeros\Src\Classes\Route delete(string $route, callable|string $handler)
 */
class Router
{
    /** @var ?array */
    private static $groupMiddlewares = null;

    /** @var array */
    private $routes = [];

    /** @var array */
    private static $methods = [
        'get',
        'post',
        'put',
        'patch',
        'delete',
        // 'head',
        // 'connect',
        // 'trace',
    ];

    /** @var Route $currentRoute */
    protected $currentRoute;

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
        $this->parseRoute($route);

        // Asign group middlewares to this route
        if (! is_null($this::$groupMiddlewares)) {
            $route->withMiddleware($this::$groupMiddlewares);
        }

        // Implement Trie-based approach.
        $this->routes[$method][$route->subdomain][] = $route;

        return $route;
    }

    /**
     * Dispatches the content for the route (URI) and method (REQUEST_METHOD).
     *
     * @throws Exception
     * @return mixed
     */
    public function dispatch(): mixed
    {
        $route = $this->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        if (! $route) {
            throw new \Exception(
                sprintf(
                    "ERROR[route] Route '%s:%s' does not match any registered route.",
                    $_SERVER['REQUEST_METHOD'],
                    $_SERVER['REQUEST_URI']
                )
            );
        }

        // Call middleware(s) for this route
        self::runMiddlewares($route->getMiddlewares());

        $this->currentRoute = $route;

        return $route
                ->handler()
                ->getContent();
    }

    /**
     * Returns the current route.
     *
     * @return Route
     */
    public function getCurrentRoute(): Route
    {
        return $this->currentRoute;
    }

    /**
     * Confirms if the URI and the REQUEST_METHOD matches any registred route.
     *
     * @param string $method
     * @param string $uri
     * @return bool|Route
     */
    public function match($method, $uri): bool|Route
    {
        // Returns URI parts and subdomain if there is any
        $currentUriParts = $this->getUriParts($uri);

        // If routes are already cached for production...
        if (in_array(env('APP_ENV'), ['production']) && empty($this->routes)) {
            $this->routes = cache('memcached')->get('cached.routes');
        }

        foreach ($this->getRoutes($method, $currentUriParts['subdomain']) as $route) {

            // URI parts are not equal: `/user/login` and `/user`, skip
            if (count($route->uriParts) !== count($currentUriParts['parts'])) {
                continue;
            }

            // In case it is URI index: '/'
            if (count($route->uriParts) == 1 && ! empty(array_diff($route->uriParts, $currentUriParts['parts']))) {
                continue;
            }

            $processNextRoute = false;

            // Checks if all URI parts from current route match the current URI
            foreach ($route->uriParts as $index => $token) {

                if (strpos($token, ':') !== false) {
                    continue;
                }

                if (strcmp($token, $currentUriParts['parts'][$index]) != 0) {
                    $processNextRoute = true;
                    break;
                }
            }

            if ($processNextRoute) {
                continue;
            }

            // Assings values to params for current route: 
            // Example: `admin.domain.com/2233/internal` to `admin:/:userid/:profile`
            // $params = ['userid'=>223, 'profile'='internal']
            $params = [];

            foreach ($route->params as $name => $index) {
                $params[$name] = $currentUriParts['parts'][$index-1];
            }

            $route->params = $params;

            return $route;
        }

        return false;
    }

    /**
     * Parses a URI and return its parts and the subdomain if there is any.
     *
     * @param string $uri
     * @return array
     */
    public function getUriParts(string $uri) : array
    {
        // Remove query string.
        // Still these GET variables can be caught with request('get') function
        $uri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $uri);

        $uriParts = [
            'parts' => array_values(
                array_filter(
                    explode('/', $uri)
                )
            ),
            'subdomain' => '*'
        ];

        // If no URI parts... add default index '/'
        if (empty($uriParts['parts'])) {
            $uriParts['parts'][] = '/';
        }

        if (substr_count($_SERVER['SERVER_NAME'], '.') == 2) {
            $uriParts['subdomain'] = explode('.', $_SERVER['SERVER_NAME'])[0];
        }

        return $uriParts;
    }

    /**
     * Destructs a route. It returns the URI parts and the subdomain if there is any.
     * 
     * Subdomain will be appended with '@'. : `admin.domain.com/login` will be [`admin@`, `loging`]
     *
     * @param Route $route
     * @return void
     */
    public function parseRoute(Route $route): void
    {
        // Replaces `{token}` for `:token`
        $route->path = preg_replace(
            '/{([^}]+)}/', 
            ':$1',
            // and `:/` for `@/`
            preg_replace("/(:\/)/", '@/', $route->path)
        );

        $tokens = explode('/', $route->path);

        $subdomain = '*';
        $uriParts  = [];
        $params    = [];

        // Go over all tokens from route path
        foreach ($tokens as $index => $token) {

            // Gets the subdomain if there is any, otherwise, sets the subdomain to '*'
            if (strpos($token, '@') !== false) {
                $subdomain = preg_replace('/@$/', '', $token) ?: '*';
            }
            
            // Determines the position of the token from the route to assign values from URI request
            // From `admin@/:userid/:profile` => `admin.domain.com/2233/internal`. URI: '/2233/internal'
            //      => ['userid': 0 and 'profile': 1]
            if (strpos($token, ':') !== false) {
                $params[$token] = $index;
                $uriParts[] = $token;
            }

            // Gets all URI parts
            if (strpos($token, ':') === false && strpos($token, '@') === false) {
                $uriParts[] = $token;
            }
        }

        // If no URI parts... add default index '/'
        if (empty($uriParts = array_filter($uriParts))) {
            $uriParts = ['/'];
        }

        $route->subdomain = $subdomain;
        $route->uriParts  = $uriParts;
        $route->params    = $params;
    }

    /**
     * Returns current registered routes from Router.
     *
     * @param string $method
     * @return array
     */
    public function getRoutes(string $method = '', string $subdomain = '*'): array
    {
        $method = strtoupper($method);

        if (! isset($this->routes[$method]) && ! empty($method)) {
            throw new \Exception("ERROR[route] Method '{$method}' is not registered.");
        }

        if (! isset($this->routes[$method][$subdomain]) && ! empty($method)) {
            throw new \Exception("ERROR[route] Subdomain '{$subdomain}' is not registered.");
        }

        if (isset($this->routes[$method])) {
            return $this->routes[$method][$subdomain];
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

    /**
     * Groups routes to run a list of middlewares on them.
     *
     * @param string|array $middlewares
     * @param callable $callable
     * @return void
     */
    public static function group(string|array $middlewares, callable $callback)
    {
        // Parses $middlewares as string: 'auth,web,api'
        if (is_string($middlewares)) {
            $middlewares = array_filter(explode(',', $middlewares));

            $appMiddlewares = config('app.middlewares');

            $middlewares = array_reduce(
                $middlewares, 
                function ($carry, $key) use ($appMiddlewares) {
                    return array_merge($carry, $appMiddlewares[$key] ?? []);
                }, 
                []
            );
        }

        self::$groupMiddlewares = array_values($middlewares);

        // Make the call to $callable.
        // This will register all routes that are in the callable body.
        call_user_func($callback);

        // Clear group middleware variable
        self::$groupMiddlewares = null;
    }

    /**
     * Load requested routes based on the current environment and URI.
     *
     * This method is responsible for loading routes depending on the web or 
     * CLI environment, handling subdomains, and validating requested routes by 
     * checking corresponding files.
     *
     * @return void
     */
    public static function loadRequestedRoutes()
    {
        // Only on web
        if (strpos(php_sapi_name(), 'cli') === false) {

            $tld = explode('.', $_SERVER['HTTP_HOST']);

            // Loads routes for subdomain
            if (count($tld) > 2 && reset($tld) != 'www' && Router::validateRequestedRoutesByFile(reset($tld))) {
                return;
            }

            // There is no subdomain
            if (count($tld) == 2) {

                // Get the URI
                $uri = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));

                if (! empty($uri) && Router::validateRequestedRoutesByFile(reset($uri))) {
                    return;
                }

                // Default routes: web.php
                Router::validateRequestedRoutesByFile();

                return;
            }
        }
    }

    /**
     * Load requested routes from a specified route file.
     *
     * This method loads routes from the specified route file and caches them 
     * in production or staging environments.
     * 
     * ```php
     * // To load the default 'web' routes:
     * Router::validateRequestedRoutesByFile();
     * ```
     * 
     * ```php
     * // To load custom routes from a file named 'custom_routes.php':
     * Router::validateRequestedRoutesByFile('custom_routes');
     * ```
     *
     * @param   string  $routeFile The name of the route file to load (default is 'web').
     * @return  bool    True if routes were successfully loaded and cached, false otherwise.
     */
    public static function validateRequestedRoutesByFile(string $routeFile = 'web')
    {
        if (file_exists($routeFile = app()->basedir . '/routes/' . $routeFile . '.php')) {
            return (require $routeFile);
        }

        return false;
    }
}
