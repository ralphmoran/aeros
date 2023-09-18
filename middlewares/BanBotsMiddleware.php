<?php

namespace Middlewares;

use Classes\Request;
use Classes\Response;
use Interfaces\MiddlewareInterface;

class BanBotsMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        // See Classes\Request properties

        // Work with $request->payload (POST) property or $request->queryParams (GET)

        // Some logic ...

        if (array_key_exists('HTTP_USER_AGENT', $_SERVER) && isset($_SERVER['HTTP_USER_AGENT'])) {
            if (preg_match('/^(Googlebot|Expanse|\'Cloud)/i', $_SERVER['HTTP_USER_AGENT'])) {
                http_response_code(301);
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . env('DOMAIN'));
                exit;
            }
        }
    }
}
