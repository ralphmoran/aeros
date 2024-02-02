<?php

namespace Aeros\App\Middlewares;

use Aeros\Src\Classes\Request;
use Aeros\Src\Classes\Response;
use Aeros\Src\Interfaces\MiddlewareInterface;

class SanitizerMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        // echo __CLASS__ . '<br />';
        // logger('From: ' . __CLASS__ . '::' . __METHOD__, app()->basedir . '/logs/event.log');
    }
}
