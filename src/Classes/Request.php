<?php

namespace Aeros\Src\Classes;

use Exception;

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

    /** @var int $api */
    private static $api = 0;

    /**
     * Initializes request params
     */
    public function __construct()
    {
        $this->url($_SERVER['PHP_SELF'])
            ->uri()
            ->setPayload($this->getPayload())
            ->headers(getallheaders())
            ->query()
            ->method()
            ->subdomain()
            ->domain();

        $this->cookies = $_COOKIE;
        $this->queryParams = $_GET;
        $this->requestParams = $_POST;
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
            $this->uri = rtrim(str_replace( '?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']), '/');
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
        $this->headers = $headers;

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
     * @param mixed $data
     * @return Request
     */
    public function setPayload(mixed $data): Request
    {
        $this->payload = $data;

        return $this;
    }

    /**
     * Sets ONLY the keys from the request that will be sent back.
     *
     * @param string|array $keys
     * @return Request
     */
    public function only(string|array $keys): Request
    {
        $this->only = is_array($keys) ? $keys : [$keys];

        return $this;
    }

    /**
     * Sets the the keys from the request that are not required.
     *
     * @param string|array $keys
     * @return Request
     */
    public function except(string|array $keys): Request
    {
        $this->except = is_array($keys) ? $keys : [$keys];

        return $this;
    }

    /**
     * Filters the ONLY and EXCEPT keys from request array, if there is no filter, 
     * it returns the original content from the request array.
     *
     * @param array $keys
     * @return array
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
            return $this->filterKeys($this->getPayload($opts), $keys);
        }

        // Sets options to make a request
        if (is_array($opts) && ! empty($opts)) {
            $this->curlOptions = $opts;
        }

        return $this;
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
                throw new \ValueError("URL is empty or does not exist.");
                return false;
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

        if (empty($this->getPayload()) && isset($this->curlOptions['payload'])) {
            $this->setPayload($this->curlOptions['payload']);
        }

        // Special format for GET and POST
        if ($this->method == 'GET' && ! empty($this->getPayload())) {
            $this->url($this->url . '?' . http_build_query($this->getPayload()));
        }
        
        if ($this->method == 'POST' && ! empty($this->getPayload())) {
            $this->setPayload(json_encode($this->getPayload()));
        }

        return [
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
            CURLOPT_POSTFIELDS     => $this->getPayload(),
        ];
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
     * Retrieves the payload data based on the specified HTTP method.
     *
     * @param   string|null     $from       The HTTP method to retrieve payload from. 
     *                                      If null, retrieves based on the current HTTP method.
     * @return  array                       The payload data associated with the specified HTTP method.
     * @throws  \BadMethodCallException     When the specified HTTP method is invalid.
     */
    public function getPayload(string $from = null)
    {
        $from ??= $this->getHttpMethod();
        $from = strtoupper($from);

        if ($from === $this->getHttpMethod()) {

            switch ($from) {
                case 'GET':
                    return array_merge($_GET, $this->payload);
                    break;
                case 'FILES':
                    return array_merge($_FILES, $this->payload);
                case 'POST':
                    return array_merge($_POST, $this->payload);
                    break;
                case 'PUT':
                    parse_str(file_get_contents('php://input'), $_PUT);
                    return array_merge($_PUT, $this->payload);
                    break;
                case 'PATCH':
                    parse_str(file_get_contents('php://input'), $_PATCH);
                    return array_merge($_PATCH, $this->payload);
                case 'DELETE':
                    parse_str(file_get_contents('php://input'), $_DELETE);
                    return array_merge($_DELETE, $this->payload);
                    break;
                default: [];
            }

        }

        if (in_array($from, $this->verbs, true)) {
            return [];
        }

        throw new \BadMethodCallException(
            sprintf(
                'ERROR[BadMethodCallException] HTTP method "%s" is invalid.', 
                $this->getHttpMethod()
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
        if (isset($this->payload[$name])) {
            return $this->payload[$name];
        }

        return null;
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
