<?php

/*
In PHP, the Request and Response classes are typically used to represent an HTTP 
request and an HTTP response, respectively. These classes typically have methods 
for accessing the various elements of the request and response, such as headers, 
cookies, query parameters, and the request body.
*/

class Request
{
    private $headers;
    private $cookies;
    private $queryParams;
    private $requestParams;
    private $body;

    public function __construct()
    {
        $this->headers = getallheaders();
        $this->cookies = $_COOKIE;
        $this->queryParams = $_GET;
        $this->requestParams = $_POST;
        $this->body = file_get_contents('php://input');
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getCookie($name)
    {
        return $this->cookies[$name];
    }

    public function getQueryParam($name)
    {
        return $this->queryParams[$name];
    }

    public function getRequestParam($name)
    {
        return $this->requestParams[$name];
    }

    public function getBody()
    {
        return $this->body;
    }
}


/*
A real world implementation of the Response class would have methods to set the 
content to json or other formats, automatically setting the content-type header 
based on the type of the content, handling and setting the content-length, 
supporting sending file and many other methods that can be useful in different 
situations.

The send() method is called at the end of the request-response cycle to actually 
send the response to the client. This method sets the response status code, 
headers and cookies, and then outputs the response content.

You can also see in the example that I provided, the response methods like 
withStatus, withHeader, withCookie and withContent all return the instance of 
the object, this is the builder pattern, it allows you to chain multiple methods 
together, to make the code more readable and easy to understand.
*/
class Response
{
    private $status;
    private $headers;
    private $cookies;
    private $content;

    public function __construct()
    {
        $this->status = 200;
        $this->headers = [];
        $this->cookies = [];
        $this->content = '';
    }

    public function withStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function withHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        $this->cookies[] = [$name, $value, $expire, $path, $domain, $secure, $httponly];
        return $this;
    }

    public function withContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function send()
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        foreach ($this->cookies as $cookie) {
            setcookie(...$cookie);
        }
        echo $this->content;
    }
}
