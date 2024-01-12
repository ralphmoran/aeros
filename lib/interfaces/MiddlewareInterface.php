<?php

namespace Aeros\Lib\Interfaces;

use Aeros\Lib\Classes\Request;
use Aeros\Lib\Classes\Response;

interface MiddlewareInterface
{
    public function __invoke(Request $request, Response $response);
}
