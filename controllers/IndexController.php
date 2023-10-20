<?php

namespace Controllers;

use Classes\ControllerBase;

class IndexController extends ControllerBase
{
    public function index()
    {
        return view('index');
    }

    public function list(int $userid)
    {
        return view('index', ['userid' => $userid]);
    }

    public function showProfile()
    {
        return 'Profile';
    }

    public function anotherProfile()
    {
        return 'Another Profile';
    }
}