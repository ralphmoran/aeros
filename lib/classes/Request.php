<?php

namespace Classes;

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

    /** @var array $payload */
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
        $this->payload = file_get_contents('php://input');
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
    public function payload(mixed $data): Request
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

        if (array_key_exists('only', $keys) && ! empty($keys['only'])) {
            $this->only($keys['only']);

            $onlyKeys = array_intersect_key($content, array_flip($this->only));
        }

        $exceptKeys = [];

        if (array_key_exists('except', $keys) && ! empty($keys['except'])) {
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

            if ($opts == 'get') {
                return $this->filterKeys($_GET, $keys);
            }

            if ($opts == 'files') {
                return $this->filterKeys($_FILES, $keys);
            }

            if ($opts == 'post' && ! empty($_POST)) {
                return $this->filterKeys($_POST, $keys);
            }

            // Other HTTP verbs: POST, PUT, DELETE, PATCH
            return $this->filterKeys(json_decode(file_get_contents('php://input'), true) ?? [], $keys);
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
            if (! array_key_exists('url', $this->curlOptions)) {
                throw new \ValueError("URL is empty or does not exist.");
                return false;
            }

            $this->url($this->curlOptions['url']);
        }

        if (array_key_exists('method', $this->curlOptions)) {
            $this->method(strtoupper($this->curlOptions['method']));
        }

        if (array_key_exists('headers', $this->curlOptions)) {
            $this->headers(
                array_merge($this->headers, $this->curlOptions['headers'])
            );
        }

        if (empty($this->payload) && array_key_exists('payload', $this->curlOptions)) {
            $this->payload($this->curlOptions['payload']);
        }

        // Special format for GET and POST
        if ($this->method == 'GET' && ! empty($this->payload)) {
            $this->url($this->url . '?' . http_build_query($this->payload));
        }
        
        if ($this->method == 'POST' && ! empty($this->payload)) {
            $this->payload(json_encode($this->payload));
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
            CURLOPT_POSTFIELDS     => $this->payload,
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

        if (in_array(strtoupper($verb), $this->verbs) && array_key_exists('url', $args) && ! empty($args['url'])) {

            $this->method($verb)
                ->url(implode('', [$args['url']]));

            return $this;
        }

        // Return from the request, ONLY the keys listed in "$args['only']"
        if (array_key_exists('only', $args) && ! empty($args['only'])) {
            $this->only($args['only']);

            return $this;
        }

        // Return from the request, all the keys EXCEPT the keys listed in "$args['except']"
        if (array_key_exists('except', $args) && ! empty($args['except'])) {
            $this->except($args['except']);

            return $this;
        }

        throw new \BadMethodCallException();
    }

    /**
     * Custom proxy to validate token and serve CORS.
     *
     * @param string $post_token
     * @param int $api = if it's an API call
     * @return bool
     */
    public static function authorized($post_token = 0, $api = 1): bool
    {
        self::$api = $api;
        $usertoken = null;

        // Token from GET
        $usertoken = array_key_exists('token', $_GET) && ! empty($_GET['token']) 
            ? $_GET['token'] 
            : null;

        if (! is_null($usertoken)) {
            if (! User::getInstance()->token_decode($usertoken)) {
                return self::forbidden();
            }
        }

        // Token from argument
        $usertoken = (! empty($post_token)) ? $post_token : null;

        if (! is_null($usertoken) && empty($_GET['token'])) {
            if (! User::getInstance()->token_decode($usertoken)) {
                return self::forbidden();
            }
        }

        // Validate token from headers
        $headers = getallheaders();

        if (empty($headers['Authorization']) && empty($headers['authorization'])) {
            return self::forbidden();
        }

        $authorization = (! empty($headers['Authorization']))
            ? $headers['Authorization']
            : $headers['authorization'];

        if (! User::getInstance()->validate_token($authorization, env("TOKEN"))) {
            return self::forbidden();
        }

        return true;
    }

    /**
     * Validates if $api is not empty, if so, returns false, otherwise sends a 403 code.
     *
     * @return bool
     */
    private static function forbidden(): bool
    {
        if (empty(self::$api)) {
            return false;
        }

        response('Forbidden', 403);

        die();
    }
}
