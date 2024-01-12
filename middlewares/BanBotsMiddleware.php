<?php

namespace Aeros\Middlewares;

use Aeros\Lib\Classes\Request;
use Aeros\Lib\Classes\Response;
use Aeros\Lib\Interfaces\MiddlewareInterface;

class BanBotsMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        // See \Aeros\Lib\Classes\Request properties

        // Work with $request->payload (POST) property or $request->queryParams (GET)

        // Some logic ...

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if (preg_match('/^(Googlebot|Expanse|\'Cloud)/i', $_SERVER['HTTP_USER_AGENT'])) {
                http_response_code(301);
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . env('HTTP_URL'));
                exit;
            }
        }
    }
}
