<?php

namespace Classes;

abstract class Observable
{
    /**
     * This method will be called when the event is triggered.
     *
     * @return Observable
     */
    abstract public function update(mixed $eventData): Observable;
}
