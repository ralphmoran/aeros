<?php

namespace Traits;

trait MagicMethods
{
    private static $methods = [];

    /**
     * Invokes static methods via Reflection.
     *
     * @param string $method
     * @param mixed|void $args
     * @return void
     */
    // public static function __callStatic($method, $args)
    // {
    //     $class = new \ReflectionClass(get_called_class());

    //     $methods = $class->getMethods(
    //         \ReflectionMethod::IS_PUBLIC
    //     );

    //     foreach ($methods as $m) {
    //         self::$methods[$m->name] = $m;
    //     }

    //     self::$methods['_' . $method]->invokeArgs(
    //         new self::$methods['_' . $method]->class, 
    //         $args
    //     );
    // }
}
