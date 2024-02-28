<?php

namespace Aeros\Src\Traits;

trait Proxyable
{
    /** @var ?string */
    public $index = null;
    
    /** @var array */
    public $objects = [];

    /**
     * This action acts as a proxy.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (method_exists($this->objects[$this->index], $method)) {
            return $this->objects[$this->index]->$method(...$args);
        }

        throw new \BadMethodCallException(
            sprintf('ERROR[BadMethodCallException] Method "%s" does not exist.', $method)
        );
    }
}
