<?php

namespace Aeros\App\Middlewares;

use Aeros\Src\Classes\Request;
use Aeros\Src\Classes\Response;
use Aeros\Src\Interfaces\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        response()->addHeaders(config('session.headers.cors'));
    }
}
