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
    public $url;

    /** @var string $method */
    public $method = 'GET';

    /** @var string|array $only $*/
    private $only = [];

    /** @var string|array $except */
    private $except = [];
    
    /** @var array $headers */
    public $headers = [
        "Content-Type:application/json"
    ];

    /** @var mixed $payload */
    public $payload = [];

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
        $this->url = $_SERVER['PHP_SELF'];
        $this->payload = $this->getPayload();
        $this->headers = getallheaders();
        $this->cookies = $_COOKIE;
        $this->queryParams = $_GET;
        $this->requestParams = $_POST;
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
     * Sets the HTTP verb
     *
     * @param string $method
     * @return Request
     */
    public function method(string $method = ''): Request
    {
        $this->method = strtoupper($method) ?: 'GET';

        return $this;
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

        curl_close($curl);

        if (! $response) {
            return curl_error($curl);
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
