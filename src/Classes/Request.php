<?php

namespace Aeros\Src\Classes;

use Exception;
use ValueError;

/**
 * Request class handles incoming requests and makes outgoing requests.
 *
 * @method public get(array $options)
 * @method public post(array $options)
 * @method public put(array $options)
 * @method public path(array $options)
 * @method public delete(array $options)
 */
final class Request
{
    /** @var string $url */
    protected $url;

    /** @var string $uri */
    protected $uri;

    /** @var bool $ssl_verifypeer */
    protected $ssl_verifypeer = true;

    /** @var string $query */
    protected $query;

    /** @var string $method */
    protected $method = 'GET';

    /** @var string $subdomain */
    protected $subdomain = '*';

    /** @var string $domain */
    protected $domain = '';

    /** @var string|array $only $*/
    private $only = [];

    /** @var string|array $except */
    private $except = [];

    /** @var array $headers */
    protected $headers = [
        "Content-Type:application/json"
    ];

    /** @var mixed $payload */
    protected $payload = [];

    /** @var array $cookies */
    public $cookies = [];

    /** @var array $queryParams */
    public $queryParams = [];

    /** @var array $requestParams */
    public $requestParams = [];

    /** @var array $curlOptions */
    private $curlOptions = [];

    /** @var array $verbs */
    private $verbs = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'FILES'
    ];

    /**
     * Initializes request params
     */
    public function __construct()
    {
        // Sanitize superglobals before assignment
        $this->cookies = $this->sanitizeInput($_COOKIE);
        $this->queryParams = $this->sanitizeInput($_GET);
        $this->requestParams = $this->sanitizeInput($_POST);

        $this->url($_SERVER['PHP_SELF'])
            ->uri()
            ->method()
            ->headers(getallheaders())
            ->query()
            ->subdomain()
            ->domain();

        // Auto-validate CSRF for state-changing requests
        if (! isMode('cli')) {
            $this->csrfValidation();
        }
    }

    /**
     * Set or get the subdomain.
     *
     * If $subdomain is provided, sets the subdomain and returns the current
     * instance.
     * If $subdomain is empty, retrieves the subdomain from the current request
     * if it exists.
     *
     * @param   string          $subdomain (optional) The subdomain to set.
     * @return  $this|string    Returns the current instance if setting the
     *                          subdomain, or the subdomain string if retrieving.
     */
    public function subdomain(string $subdomain = '')
    {
        if (! empty($subdomain)) {
            $this->subdomain = $subdomain;

            return $this;
        }

        if (! isMode('cli')) {

            $tld = explode('.', $_SERVER['HTTP_HOST']);

            // There is subdomain
            if (count($tld) > 2 && reset($tld) != 'www') {

                $this->subdomain = $tld[0];

                array_shift($tld);

                $this->domain(implode('.', $tld));
            }
        }

        return $this;
    }

    /**
     * Get the current subdomain.
     *
     * @return  string|null     The current subdomain, or null if not set.
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    /**
     * Set or get the domain.
     *
     * If $domain is provided, sets the domain and returns the current instance.
     * If $domain is empty, retrieves the domain from the current request if it
     * exists.
     *
     * @param   string          $domain (optional) The domain to set.
     * @return  $this|string    Returns the current instance if setting the
     *                          domain, or the domain string if retrieving.
     */
    public function domain(string $domain = '')
    {
        if (! empty($domain)) {
            $this->domain = $domain;

            return $this;
        }

        if (! isMode('cli')) {

            $tld = explode('.', $_SERVER['HTTP_HOST']);

            if (count($tld) == 2) {
                $this->domain = implode('.', $tld);
            }
        }

        return $this;
    }

    /**
     * Get the current domain.
     *
     * @return  string|null     The current domain, or null if not set.
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Sets the target URL
     *
     * @param string $url
     * @return Request
     */
    public function url(string $url): Request
    {
        $maxLength = config('security.max_url_length', 2048);

        if (strlen($url) > $maxLength) {
            throw new ValueError("URL exceeds maximum length of {$maxLength} characters");
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Sets the target URI.
     *
     * @param string $uri
     * @return Request
     */
    public function uri(string $uri = ''): Request
    {
        if (! empty($uri)) {
            $this->uri = $uri;

            return $this;
        }

        if (! isMode('cli')) {
            $this->uri = rtrim(
                str_replace(
                    '?' . $_SERVER['QUERY_STRING'],
                    '',
                    $_SERVER['REQUEST_URI']
                ),
                '/'
            );
        }

        return $this;
    }

    /**
     * Returns the current URI.
     *
     * @return string
     */
    public function getURI(): string
    {
        return $this->uri;
    }

    /**
     * Sets the ssl_verifypeer parameter for the Curl request.
     *
     * WARNING: Disabling SSL verification is a security risk and should only
     * be used in development environments.
     *
     * @param   bool    $ssl Activates or deactivates the SSL request.
     * @return  Request
     * @throws  \Exception
     */
    public function ssl(bool $ssl): Request
    {
        // Only allow disabling SSL in non-production modes
        if (! $ssl && isEnv('production')) {
            throw new \Exception(
                "SECURITY WARNING: SSL verification cannot be disabled in production. " .
                "This would expose the application to Man-in-the-Middle attacks."
            );
        }

        // Log warning even in development
        if (! $ssl) {
            logger(
                "WARNING: SSL verification has been disabled. " .
                "This should NEVER be used in production environments.",
                app()->basedir . '/logs/security.log'
            );
        }

        $this->ssl_verifypeer = $ssl;

        return $this;
    }

    /**
     * Sets or updates the query string for the current request.
     *
     * @param string $query
     * @return Request
     */
    public function query(string $query = ''): Request
    {
        if (! empty($query)) {
            $this->query = $query;

            return $this;
        }

        if (! isMode('cli')) {
            $this->query = $_SERVER['QUERY_STRING'];
        }

        return $this;
    }

    /**
     * Returns the query string from request.
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Sets the HTTP verb
     *
     * @param string $method
     * @return Request
     */
    public function method(string $method = ''): Request
    {
        if (! empty($method)) {
            $this->method = strtoupper($method);

            return $this;
        }

        if (! isMode('cli')) {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }

        return $this;
    }

    /**
     * Gets the HTTP verb.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sets the headers
     *
     * @param array $headers
     * @return Request
     */
    public function headers(array $headers): Request
    {
        $maxCount = config('security.max_header_count', 50);

        if (count($headers) > $maxCount) {
            throw new ValueError("Too many headers. Maximum allowed: {$maxCount}");
        }

        $normalized = [];

        foreach ($headers as $k => $v) {

            $k = str_replace(["\r", "\n"], '', $k);
            $v = str_replace(["\r", "\n"], '', $v);

            $normalized[] = is_int($k) ? $v : $k . ': ' . $v;
        }

        $this->headers = $normalized;

        return $this;
    }

    /**
     * Gets the headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets the value for data
     *
     * @param   mixed   $data
     * @param   bool    $merge If true, merges with existing payload. If false, replaces it.
     * @return  Request
     */
    public function setPayload(mixed $data, bool $merge = false): Request
    {
        if ($merge) {
            $this->payload = is_array($data)
                ? array_merge( (array) $this->payload, $data)
                : $data;

            return $this;
        }

        $this->payload = $data;

        return $this;
    }

    /**
     * Retrieves the payload data based on the specified HTTP method.
     *
     * @param   string|null     $from       The HTTP method to retrieve payload from.
     *                                      If null, retrieves based on the current HTTP method.
     * @return  array                       The payload data associated with the specified HTTP method.
     * @throws  \BadMethodCallException|Exception     When the specified HTTP method is invalid.
     */
    public function getPayload(string $from = null)
    {
        $from ??= $this->getHttpMethod();
        $from = strtoupper($from);

        if (in_array($from, $this->verbs, true)) {

            switch ($from) {
                case 'GET':
                    return $this->queryParams;
                case 'FILES':
                    return $_FILES;
                case 'POST':
                    if (empty($this->requestParams) && ! empty($input = $this->readInputStream())) {

                        if ($this->isJson()) {
                            return json_decode($input,true) ?? [];
                        }

                        parse_str($input, $_POST_INPUT);

                        return $this->sanitizeInput($_POST_INPUT ?? []);
                    }

                    return $this->requestParams;
                case 'PUT':
                    parse_str($this->readInputStream(), $_PUT);

                    return $_PUT;
                case 'PATCH':
                    parse_str($this->readInputStream(), $_PATCH);

                    return $_PATCH;
                case 'DELETE':
                    parse_str($this->readInputStream(), $_DELETE);

                    return $_DELETE;
                default: return [];
            }
        }

        throw new \BadMethodCallException(
            sprintf(
                'ERROR[BadMethodCallException] HTTP method "%s" is invalid.',
                $this->getMethod()
            )
        );
    }

    /**
     * Set or get cookies.
     *
     * If an array of cookies is passed, it will replace the current cookies.
     * If no cookies are passed, the current cookies will remain unchanged.
     *
     * @param   array   $cookies Optional. An array of cookies to set. Default
     *                          is an empty array.
     *
     * @return  self    Returns the current instance to allow method chaining.
     */
    public function cookies(array $cookies = []): Request
    {
        if (! empty($cookies)) {

            $maxSize = config('security.max_cookie_size', 4096);
            $size = strlen(serialize($cookies));

            if ($size > $maxSize) {
                throw new ValueError("Cookies size exceeds maximum of {$maxSize} bytes");
            }

            $this->cookies = $cookies;
        }

        return $this;
    }

    /**
     * Retrieve the current cookies.
     *
     * This method returns the current set of cookies stored in the object.
     *
     * @return array The current cookies stored in the object.
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Sets ONLY the keys from the request that will be sent back.
     *
     * @param   string|array    $keys
     * @return  Request
     */
    public function only(string|array $keys): Request
    {
        $this->only = is_array($keys) ? $keys : [$keys];

        return $this;
    }

    /**
     * Sets the the keys from the request that are not required.
     *
     * @param   string|array    $keys
     * @return  Request
     */
    public function except(string|array $keys): Request
    {
        $this->except = is_array($keys) ? $keys : [$keys];

        return $this;
    }

    /**
     * Sends or executes a cURL request.
     *
     * The result will be in JSON format.
     *
     * @return string|boolean
     * @throws Exception|ValueError
     */
    public function send(): string|bool
    {
        // Validates before cURL init action
        $cURLOpts = $this->validateOpts();

        $curl = curl_init();

        curl_setopt_array($curl, $cURLOpts);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if (! $response) {
            return $error;
        }

        return response($response);
    }

    /**
     * Retrieves the HTTP method of the current request.
     *
     * @return  string  The HTTP method (e.g., GET, POST, PUT, DELETE, PATCH).
     */
    public function getHttpMethod()
    {
        if (isMode('cli')) {
            return 'GET';
        }

        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Validates if the current request is JSON type.
     *
     * @return  bool
     */
    public function isJson(): bool
    {
        foreach ($this->headers as $h) {
            if (stripos($h, 'content-type:') === 0 && stripos($h, 'application/json') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets all options for cURL call
     *
     * @param mixed $opts
     * @param array $keys
     * @return mixed
     */
    public function setOptions(mixed $opts, array $keys): mixed
    {
        // Return values from current request
        if (is_string($opts) && in_array(strtoupper($opts), $this->verbs)) {
            return $this->filterKeys(
                $this->getPayload($opts),
                $keys
            );
        }

        // Sets options to make a request
        if (is_array($opts) && ! empty($opts)) {
            $this->curlOptions = $opts;
        }

        return $this;
    }

    /**
     * Validates and filters payload against defined rules.
     *
     * @param   array   $filters Filter rules (same format as filter_var_array)
     * @param   bool    $throwOnError If true, throws exception on validation failure
     * @return  array   Filtered and validated payload
     * @throws  ValueError|Exception When validation fails and $throwOnError is true
     */
    public function validate(array $filters, bool $throwOnError = true): array
    {
        $payload = $this->getPayload();

        $filtered = filter_var_array($payload, $filters, true);

        if ($throwOnError) {
            $errors = [];

            foreach ($filters as $key => $filter) {
                // Check if validation failed (returns false for invalid data)
                if (array_key_exists($key, $filtered) && $filtered[$key] === false) {
                    $errors[$key] = "Validation failed for field: {$key}";
                }

                // Check for required fields that are null
                if (array_key_exists($key, $filtered) && $filtered[$key] === null && isset($payload[$key])) {
                    $errors[$key] = "Invalid value for field: {$key}";
                }
            }

            if (! empty($errors)) {
                throw new ValueError("Validation failed: " . json_encode($errors));
            }
        }

        return $filtered;
    }

    /**
     * Validates a single field from the payload.
     *
     * @param   string      $field Field name
     * @param   int|array   $filter Filter constant or array with filter options
     * @return  mixed       Filtered value or null if validation fails
     * @throws  Exception
     */
    public function validateField(string $field, int|array $filter): mixed
    {
        $payload = $this->getPayload();

        if (!isset($payload[$field])) {
            return null;
        }

        return filter_var($payload[$field], $filter);
    }

    /**
     * Gets validation rules helper - returns common filter patterns.
     *
     * @return  array
     */
    public static function validationRules(): array
    {
        return [
            'email' => FILTER_VALIDATE_EMAIL,
            'url' => FILTER_VALIDATE_URL,
            'ip' => FILTER_VALIDATE_IP,
            'int' => FILTER_VALIDATE_INT,
            'float' => FILTER_VALIDATE_FLOAT,
            'boolean' => FILTER_VALIDATE_BOOLEAN,
            'string' => FILTER_SANITIZE_STRING,
            'encoded' => FILTER_SANITIZE_ENCODED,
            'special_chars' => FILTER_SANITIZE_SPECIAL_CHARS,
        ];
    }

    /**
     * Quick validation with predefined rules.
     *
     * @param   array   $rules ['field' => 'email', 'age' => 'int']
     * @return  array   Validated data
     * @throws  Exception
     */
    public function validateWith(array $rules): array
    {
        $filters = [];
        $patterns = self::validationRules();

        foreach ($rules as $field => $rule) {
            if (is_string($rule) && isset($patterns[$rule])) {
                $filters[$field] = $patterns[$rule];
            }

            if (is_array($rule) || is_int($rule)) {
                $filters[$field] = $rule;
            }
        }

        return $this->validate($filters);
    }

    /**
     * Filters the ONLY and EXCEPT keys from request array, if there is no filter,
     * it returns the original content from the request array.
     *
     * @param array $content
     * @param array $keys
     * @return  array
     */
    private function filterKeys(array $content, array $keys): array

    {
        $onlyKeys = [];

        if (isset($keys['only'])) {
            $this->only($keys['only']);

            $onlyKeys = array_intersect_key($content, array_flip($this->only));
        }

        $exceptKeys = [];

        if (isset($keys['except'])) {
            $this->except($keys['except']);

            $exceptKeys = array_diff_key($content, array_flip($this->except));
        }

        $newKeys = array_merge($onlyKeys, $exceptKeys);

        return empty($newKeys) ? $content : $newKeys;
    }

    /**
     * Validate cURL options and sets basic curlOptions.
     *
     * @return bool|array
     * @throws ValueError
     */
    private function validateOpts(): bool|array
    {
        if (empty($this->url)) {
            if (! isset($this->curlOptions['url'])) {
                throw new ValueError("URL is empty or does not exist.");
            }

            $this->url($this->curlOptions['url']);
        }

        if (isset($this->curlOptions['method'])) {
            $this->method(strtoupper($this->curlOptions['method']));
        }

        if (isset($this->curlOptions['headers'])) {
            $this->headers(
                array_merge($this->headers, $this->curlOptions['headers'])
            );
        }

        if (empty($this->payload) && isset($this->curlOptions['payload'])) {
            $this->setPayload($this->curlOptions['payload']);
        }

        // Special format for GET and POST
        if ($this->method == 'GET' && ! empty($this->payload)) {
            $this->url($this->url . '?' . http_build_query($this->payload));
        }

        // Process cookies
        $cookies = '';

        foreach ($this->getCookies() as $k => $v) {
            $cookies .= urlencode($k) . '=' . urlencode($v) . ';';
        }

        $opts = [
            CURLOPT_VERBOSE        => true,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_CUSTOMREQUEST  => $this->method,
            CURLOPT_HTTPHEADER     => $this->headers,
            CURLOPT_URL            => $this->url,
            CURLOPT_SSL_VERIFYPEER => $this->ssl_verifypeer,
            CURLOPT_COOKIE         => $cookies,
        ];

        if ($this->method == 'POST' && ! empty($this->payload)) {

            $this->setPayload($this->payload); // e.g., form-encoded array

            if ($this->isJson() && is_array($this->payload)) {
                $this->setPayload(json_encode(
                    $this->payload,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ));
            }

            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $this->payload;
        }

        return $opts;
    }

    /**
     * Safely reads input from php://input with size limits.
     *
     * @return  string      The input data
     * @throws  Exception   If reading fails or exceeds size limit
     */
    private function readInputStream(): string
    {
        $maxSize = config('security.max_input_stream_size', 10485760);

        $input = stream_get_contents(
            fopen('php://input', 'r'),
            $maxSize + 1
        );

        if ($input === false) {
            throw new Exception("Failed to read input stream");
        }

        if (strlen($input) > $maxSize) {
            throw new Exception("Input size exceeds maximum allowed limit of {$maxSize} bytes");
        }

        return $this->sanitizeInput($input);
    }

    /**
     * Sanitizes input data recursively.
     *
     * @param mixed $data
     * @return mixed
     */
    private function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }

        if (is_string($data)) {
            // Remove null bytes
            $data = str_replace("\0", '', $data);

            // Trim whitespace
            $data = trim($data);
        }

        return $data;
    }

    /**
     * Validates URL safety to prevent SSRF attacks.
     * Can be disabled for trusted internal networks.
     *
     * @param string $url
     * @return bool
     * @throws ValueError
     */
    private function validateUrlSafety(string $url): bool
    {
        // Check if SSRF protection is enabled (default: true in production)
        $ssrfProtection = config('security.ssrf_protection', isEnv('production'));

        if (! $ssrfProtection) {
            return true; // Skip validation for trusted environments
        }

        $parsed = parse_url($url);

        if (! $parsed || ! isset($parsed['scheme']) || ! isset($parsed['host'])) {
            throw new ValueError("Invalid URL format");
        }

        // Only allow http and https
        if (! in_array($parsed['scheme'], ['http', 'https'])) {
            throw new ValueError("Only HTTP and HTTPS protocols are allowed");
        }

        $host = $parsed['host'];

        // Block common metadata endpoints (cloud services)
        $blockedHosts = config('security.blocked_hosts', [
            '169.254.169.254', // AWS/Azure/GCP metadata
            'metadata.google.internal',
            '100.100.100.200', // Alibaba Cloud
        ]);

        if (in_array(strtolower($host), $blockedHosts)) {
            throw new ValueError("Access to metadata endpoints is blocked");
        }

        // Check if host is an IP
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Block only if configured (allow internal IPs by default)
            $blockPrivateIps = config('security.block_private_ips', false);

            if ($blockPrivateIps) {
                if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    throw new ValueError("Access to private IP ranges is blocked");
                }
            }
        }

        return true;
    }

    /**
     * Validates CSRF token for state-changing requests.
     *
     * @throws Exception
     */
    private function csrfValidation(): void
    {
        $csrfProtection = config('security.csrf_protection', true);

        // Skip if disabled or GET request
        if (! $csrfProtection || $this->method === 'GET') {
            return;
        }

        // Skip for JSON API requests (use other auth like Bearer tokens)
        if ($this->isJson()) {
            return;
        }

        // Validate for POST/PUT/PATCH/DELETE
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {

            $token = $this->getPayload()['csrf_token'] ?? request()->headers()['X-CSRF-TOKEN'] ?? null;

            if (! app()->security->validateCsrfToken($token)) {
                throw new \Exception("CSRF token validation failed", 403);
            }
        }
    }

    /**
     * Magic method to set the request method
     *
     * @param string $verb
     * @param array $url
     * @return Request
     * @throws BadMethodCallException
     */
    public function __call($verb, $args): Request
    {
        if (isset($args[0]) && filter_var($args[0], FILTER_VALIDATE_URL)) {
            $args['url'] = $args[0];

            unset($args[0]);
        }

        if (in_array(strtoupper($verb), $this->verbs) && isset($args['url'])) {

            $this->method($verb)
                ->url(implode('', [$args['url']]));

            return $this;
        }

        // Return from the request, ONLY the keys listed in "$args['only']"
        if (isset($args['only'])) {
            $this->only($args['only']);

            return $this;
        }

        // Return from the request, all the keys EXCEPT the keys listed in "$args['except']"
        if (isset($args['except'])) {
            $this->except($args['except']);

            return $this;
        }

        throw new \BadMethodCallException(
            sprintf(
                'ERROR[BadMethodCallException] HTTP method "%s" is invalid.',
                $verb
            )
        );
    }

    /**
     * Magic method to set session variables.
     *
     * @param   string  $name   The session variable name.
     * @param   mixed   $value  The session variable value.
     */
    public function __set($name, $value)
    {
        $this->payload[$name] = $value;
    }

    /**
     * Magic method to get session variables.
     *
     * @param   string  $name   The session variable name.
     * @return  mixed|null  The session variable value if set, otherwise null.
     */
    public function __get($name)
    {
        return $this->payload[$name] ?? null;
    }

    /**
     * Magic method to check if a session variable is set.
     *
     * @param   string  $name   The session variable name.
     * @return  bool    Returns true if the session variable is set, otherwise false.
     */
    public function __isset($name)
    {
        return isset($this->payload[$name]);
    }

    /**
     * Magic method to unset a session variable.
     *
     * @param   string  $name   The session variable name.
     * @return  bool    Returns true on success.
     */
    public function __unset($name)
    {
        if (isset($this->payload[$name])) {
            unset($this->payload[$name]);
        }

        return true;
    }
}
