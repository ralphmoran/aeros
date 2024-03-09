<?php

namespace App\Middlewares;

use Aeros\Src\Classes\Request;
use Aeros\Src\Classes\Response;
use Aeros\Src\Interfaces\MiddlewareInterface;

class VerifyCsrfTokenMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        if (request()->getHttpMethod() === 'POST' && isset(request()->csrf_token)) {
            if (! hash_equals((string) session()->csrf_token, request()->csrf_token)) {
                abort('403 Forbidden');
            }
        }
    }
}
