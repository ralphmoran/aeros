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

        header("Access-Control-Allow-Origin: " . (array_key_exists('HTTP_ORIGIN', $_SERVER) 
                                            ? $_SERVER['HTTP_ORIGIN'] 
                                            : $_SERVER['HTTP_HOST'])
                                        );
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE');
        header('Access-Control-Allow-Headers: Origin, Authorization, Content-type');
        header("Access-Control-Max-Age: 3600");
    }
}
