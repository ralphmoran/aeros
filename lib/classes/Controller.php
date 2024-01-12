<?php

namespace Aeros\Lib\Classes;

use Aeros\Models\User;
use Aeros\Models\Role;

# TODO: Add conditional caching system based on request
# TODO: Add async taks handling
# TODO: Add lazy loading or eager loading
# TODO: Add event handling and triggering
class Controller
{
    public function __construct()
    {
        # TODO: Add request validation...? Or, middleware does this job...?
        // logger(
        //     'Controller __construct method has called in: ' . get_called_class() . '. ', 
        //     app()->basedir . '/logs/error.log'
        // );
    }
}
