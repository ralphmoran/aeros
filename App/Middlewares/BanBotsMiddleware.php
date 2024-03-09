<?php

namespace App\Middlewares;

use Aeros\Src\Classes\Request;
use Aeros\Src\Classes\Response;
use Aeros\Src\Interfaces\MiddlewareInterface;

class BanBotsMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if (preg_match('/^(Googlebot|Expanse|\'Cloud)/i', $_SERVER['HTTP_USER_AGENT'])) {

                response()
                    ->addHeaders([
                        'Location' => env('HTTP_URL'),
                        'HTTP/1.1 301 Moved Permanently'
                    ])
                    ->withResponseCode(301);
            }
        }
    }
}
