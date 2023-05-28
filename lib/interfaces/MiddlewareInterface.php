<?php

namespace Interfaces;

use Classes\Request;
use Classes\Response;

interface MiddlewareInterface
{
    public function __invoke(Request $request, Response $response);
}
