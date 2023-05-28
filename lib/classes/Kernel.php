<?php

namespace Classes;

abstract class Kernel
{
    /** @var int Counter self instances */
    public static $counter = 0;

    /** @var mixed $instance can be an object from View, Response or 
     * any other class that uses singleton pattern.
     */
    private static $instances = [];

    /**
     * Constructor has to be protected to avoid instantiation from outside.
     */
    protected function __construct() {}

    /**
     * Singletons should not be cloneable.
     */
    protected function __clone() {}

    /**
     * Singletons should not be restorable from strings.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Returns the singleton instance.
     *
     * @return mixed
     */
    public static function getInstance()
    {
        $cls = static::class;

        if (! isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }
}
