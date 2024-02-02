<?php

namespace Aeros\Src\Classes;

use Aeros\App\Models\User;
use Aeros\App\Models\Role;

class Controller
{
    public function __construct()
    {
        // Add request validation...? Or, middleware does this job...?
        // Add conditional caching system based on request
        // Add async taks handling
        // Add lazy loading or eager loading
        // Add event handling and triggering

        // logger(
        //     'Controller __construct method has called in: ' . get_called_class() . '. ', 
        //     app()->basedir . '/logs/error.log'
        // );
    }
}
