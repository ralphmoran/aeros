<?php

namespace Controllers;

use Classes\ControllerBase;

class AppController extends ControllerBase
{
    public function index()
    {
        return view('index');
    }
}