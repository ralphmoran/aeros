<?php

namespace Controllers;

use Classes\ControllerBase;

class IndexController extends ControllerBase
{
    public function index()
    {
        return view('index');
    }
}