<?php

namespace Aeros\Lib\Classes;

abstract class Observable
{
    protected $data;

    /**
     * This method will be called when the event is triggered.
     *
     * @return bool
     */
    abstract public function update(mixed $eventData): bool;
}
