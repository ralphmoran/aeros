<?php

namespace Classes;

abstract class Observable
{   
    /**
     * Generic method for all observers.
     *
     * @param string $message
     * @return void
     */
    public function log(string $message)
    {
        sprintf("Log: %s (%s::%s)", $message, __CLASS__, __LINE__);
    }

    /**
     * This method will be called when the event is triggered.
     *
     * @return Observable
     */
    abstract public function update(mixed $eventData): Observable;
}
