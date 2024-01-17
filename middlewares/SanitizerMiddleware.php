<?php

namespace Aeros\Middlewares;

use Aeros\Lib\Classes\Request;
use Aeros\Lib\Classes\Response;
use Aeros\Lib\Interfaces\MiddlewareInterface;

class SanitizerMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response)
    {
        // echo __CLASS__ . '<br />';
        // logger('From: ' . __CLASS__ . '::' . __METHOD__, app()->basedir . '/logs/event.log');
    }
}
