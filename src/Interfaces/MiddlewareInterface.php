<?php

namespace Aeros\Src\Interfaces;

use Aeros\Src\Classes\Request;
use Aeros\Src\Classes\Response;

interface MiddlewareInterface
{
    public function __invoke(Request $request, Response $response);
}
