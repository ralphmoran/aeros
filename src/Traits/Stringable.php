<?php

namespace Aeros\Src\Traits;

/**
 * Trait Stringable
 *
 * A trait to serialize an object to a string.
 *
 * @package Aeros\Src\Traits
 */
trait Stringable
{
    /**
     * Serialize the object to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return serialize($this);
    }
}
