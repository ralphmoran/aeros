<?php

namespace Classes;

final class RouteV2
{
    /** @var array */
    private static $routes = [];

    /** @var string */
    private static $subdomain;

    /**
     * Resolves the current request and determines 
     * which Controller and method to use.
     *
     * @return void
     */
    public static function resolve() // Goes into Router
    {
        // Bad request: Return 400 error
        self::bad_request();

        try {
            // Check if it's a file
            self::parse_file();

            // Check if it's a valid route
            self::valid_route();

            // Route does not exist: Return 404 error
            self::invalid_route();

        } catch (\Exception $e) {
            printf($e->getMessage());
        }
    }

    /**
     * Banns undesired bots
     *
     * @return void
     */
    public static function bann_bots() // Middleware
    {
        if (array_key_exists('HTTP_USER_AGENT', $_SERVER) && isset($_SERVER['HTTP_USER_AGENT'])) {
            if (preg_match('/^(Googlebot|Expanse|\'Cloud)/i', $_SERVER['HTTP_USER_AGENT'])) {
                http_response_code(301);
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: https://myaero.app/");
                exit;
            }
        }
    }

    /**
     * Parses the domain from the current request.
     *
     * @return void
     */
    private static function parse_domain()
    {
        if (filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_IP)) {
            self::$subdomain   = '';

            return;
        }

        $domain_parts = explode('.', $_SERVER['HTTP_HOST']);

        self::$subdomain = (count($domain_parts) == 3) ? $domain_parts[0] : '';
    }

    /**
     * Processes and pushes a new route into self::$routes array.
     *
     * @param string $request_method
     * @param array $route_params
     * @return void
     */
    // private static function addRoute(string $method, string $path, $handler)
    private static function addRoute(string $request_method, array $route_params)
    {
        if (empty($route_params)) {
            return;
        }

        // Check if there is a subdomain
        preg_match("/^(\w+):\/.*/", "", $matches);

        $subdomain = $matches[1] ?? null;

        if ($subdomain) {

        }


        $path = preg_replace('/{([^}]+)}/', ':$1', $path);
        $tokens = explode('/', $path);
        $params = [];

        foreach ($tokens as $i => $token) {
            if (strpos($token, ':')) {
                $name = substr($token, 1);
                $params[$name] = $i;
            }
        }

        $this->routes[$method][$path] = [
            'handler' => $handler,
            'params' => $params
        ];




        try {
            $full_uri   = array_key_exists('uri', $route_params) 
                            ? $route_params['uri'] 
                            : $route_params[0];

            $controller = array_key_exists('controller', $route_params) 
                            ? $route_params['controller'] 
                            : $route_params[1];

            /*
            |-------------------------------------------
                Format (subdomain)

                Route::get("lender:/login", "LenderLoginController");
            |-------------------------------------------
            */

            if (strpos($full_uri, ':')) {

                [$subdomain, $cleaned_uri] = explode(':', $full_uri);

                if (self::is_route_registered($full_uri, $request_method, $subdomain)) {
                    throw new \Exception('ERROR[Route] Route duplicated: <strong>' 
                        . strtoupper($request_method) 
                        . ':' 
                        . $full_uri 
                        . '</strong>');
                }

                self::$routes[$request_method][$subdomain][] = [
                    'full_uri'   => $full_uri,
                    'uri'        => $cleaned_uri,
                    'controller' => $controller
                ];

                return;
            }

            /*
            |-------------------------------------------
                Format (no subdomain): 

                Route::get("/login", "LenderLoginController");
            |-------------------------------------------
            */

            if (self::is_route_registered($full_uri, $request_method)) {
                throw new \Exception('ERROR[Route] Route duplicated: ' 
                    . strtoupper($request_method) 
                    . ':' 
                    . $full_uri 
                    . '</strong>');
            }

            self::$routes[$request_method][] = [
                'full_uri'   => $full_uri,
                'uri'        => $full_uri,
                'controller' => $controller
            ];

        } catch (\Exception $e) {
            view('common.errors.codes', [
                'code' => '400 - Invalid route',
                'message' => $e->getMessage(),
            ]);
            exit;
        }
    }

    /**
     * Process the current call if it's a file.
     *
     * @return void
     */
    private static function parse_file()
    {
        $requested_file = '/var/www/html' . $_SERVER['PHP_SELF'];

        if (file_exists($requested_file)) {

            if (preg_match('/^.*\.(css|gif|ico|jpe?g|jpg|js|png|csv|xml|pdf|svg|woff|woff2|ttf|map)$/i', $requested_file, $matches)) {

                response('', $matches[1]);
                echo file_get_contents($requested_file);

                exit();
            }

        }
    }

    /**
     * Processes the current request and returns a valid view.
     *
     * @return void
     */
    private static function valid_route()
    {
        self::parse_domain();

        $filtered_routes = (! empty(self::$subdomain) && array_key_exists(self::$subdomain, self::$routes[$_SERVER['REQUEST_METHOD']]))
                                ? self::$routes[$_SERVER['REQUEST_METHOD']][self::$subdomain] 
                                : self::$routes[$_SERVER['REQUEST_METHOD']];

        // Where is this route registered?...
        if (($route_found_at = array_search(
                                    $_SERVER['PHP_SELF'], 
                                    array_column($filtered_routes, 'uri')
                                )) !== false) {

            $contoller_and_method = $filtered_routes[$route_found_at]['controller'];

            [$controller_name, $method] = strpos($contoller_and_method, '@') === false
                                            ? [$contoller_and_method, 'index']
                                            : explode('@', $contoller_and_method);

            $controller_name = "Controllers\\$controller_name";

            if (! class_exists($controller_name) || get_parent_class($controller_name) != 'Classes\\ControllerBase') {
                throw new \Exception(
                        sprintf('ERROR[Controller] There was a problem trying to validate controller \'%s\.', $controller_name)
                    );
            }

            $method = empty($method) ? 'index' : $method;

            if (! method_exists($controller_name, $method)) {
                throw new \Exception(
                        sprintf('ERROR[Controller] Method \'%s\'::\'%s\' does not exist.', $controller_name, $method)
                    );
            }

            http_response_code(200);

            print ($controller_name::getInstance())->$method();

            exit;
        }
    }

    /**
     * Returns 404 - Not found view.
     *
     * @return void
     */
    private static function invalid_route()
    {
        http_response_code(404);

        print(view('common.errors.codes', [
                                            'code'    => '404 - Not found',
                                            'details' => sprintf('ERROR[Route] Route <strong>"%s:%s:%s"</strong> does not exist.', 
                                                                    self::$subdomain, 
                                                                    $_SERVER['REQUEST_METHOD'], 
                                                                    rtrim($_SERVER['PHP_SELF'])
                                                                )
                                        ]
                                    )
                                );
    }

    /**
     * Returns a 400 - Bad request view.
     *
     * @return void
     */
    private static function bad_request()
    {
        self::bann_bots();

        if (strtoupper($_SERVER['REQUEST_METHOD']) != 'OPTIONS' && empty(self::$routes[$_SERVER['REQUEST_METHOD']])) {

            // Return 400 error
            http_response_code(400);

            print(view('common.errors.codes', [
                        'code'    => '400 - Bad Request',
                        'message' => 'The request could not be resolved.',
                        'details' => sprintf('ERROR[Route] REQUEST_METHOD \'%s\' unavailable.', $_SERVER['REQUEST_METHOD'])
                    ]
                )
            );

            die();
        }

        // If OPTIONS, return 200 code inmediately
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Fills out the $routes array.
     *
     * @param array $routes
     * @return void
     */
    public static function set_routes(array $routes)
    {
        self::$routes = $routes;
    }

    /**
     * Returns the $routes array.
     *
     * @return array
     */
    public static function get_routes() : array
    {
        return self::$routes;
    }

    /**
     * Checks if a route is already registered.
     *
     * @param string $full_uri
     * @param string $request_method
     * @return boolean
     */
    private static function is_route_registered(
        string $route,
        string $request_method = 'GET',
        string $subdomain = ''
    ) : bool
    {
        if (env('APP_ENV') === 'development' || env('APP_ENV') === 'staging') {

            if (! empty(self::$routes) && array_key_exists($request_method, self::$routes)) {
                $tmp_routes = self::$routes[$request_method];

                if (! empty($subdomain) && array_key_exists($subdomain, self::$routes[$request_method])) {
                    $tmp_routes = self::$routes[$request_method][$subdomain];
                }

                return array_search($route, array_column($tmp_routes, 'full_uri')) === false 
                    ? false 
                    : true;
            }
        }

        // Only for PRODUCTION
        return false;
    }

    /**
     * Registers a GET route.
     *
     * @param mixed $route_params
     * @return void
     */
    public static function get(...$route_params)
    {
        self::addRoute('GET', $route_params);
    }
    
    /**
     * Registers a POST route.
     *
     * @param mixed $route_params
     * @return void
     */
    public static function post(...$route_params)
    {
        self::addRoute('POST', $route_params);
    }
    
    /**
     * Registers a PUT route.
     *
     * @param mixed $route_params
     * @return void
     */
    public static function put(...$route_params)
    {
        self::addRoute('PUT', $route_params);
    }

    /**
     * Registers a PATCH route.
     *
     * @param mixed $route_params
     * @return void
     */
    public static function patch(...$route_params)
    {
        self::addRoute('PATCH', $route_params);
    }

    /**
     * Registers a DELETE route.
     *
     * @param mixed $route_params
     * @return void
     */
    public static function delete(...$route_params)
    {
        self::addRoute('DELETE', $route_params);
    }

    /**
     * Sets all required header for no cache.
     *
     * @return void
     */
    public static function set_no_cache()
    {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Returns the cached view and excludes any requested route.
     *
     * @param string $uri
     * @param array $exclude - List of excluded routes to not get them from cache. 
     * @return void
     */
    public static function get_cached_view(string $uri, array $exclude = [])
    {
        if (env('CACHE')) {

            foreach ($exclude[strtolower($_SERVER['REQUEST_METHOD'])] as $route) {
                [$exc_subdomain, $exc_url] = explode(":", $route);

                $exc_uri = env('HTTP_SECURE') . ($exc_subdomain ? $exc_subdomain . '.' : '') . env('BASE_URL') . $exc_url;

                // Not to get view from cache
                if ($uri == $exc_uri) {
                    return;
                }
            }

            $hashed_uri = md5($uri);

            if (cache()->exists($hashed_uri)) {
                http_response_code(200);

                print cache()->get($hashed_uri);

                exit;
            }
        }
    }
}
