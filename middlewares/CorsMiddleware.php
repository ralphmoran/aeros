<?php

namespace Middlewares;

use Classes\Request;
use Classes\Response;
use Interfaces\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        // echo __CLASS__ . '<br />';
        // $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        // $response = $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Origin, Authorization');
        // $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }
}
