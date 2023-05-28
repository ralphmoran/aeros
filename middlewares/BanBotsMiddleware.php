<?php

namespace Middlewares;

use Classes\Request;
use Classes\Response;
use Interfaces\MiddlewareInterface;

class BanBotsMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        echo __CLASS__ . '<br />';
        // return $next($request, $response);
    }
}
