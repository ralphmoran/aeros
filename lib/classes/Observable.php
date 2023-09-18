<?php

namespace Classes;

abstract class Observable
{   
    /**
     * Generic method for all observers.
     *
     * @param mixed $message
     * @return void
     */
    public function log(mixed $message)
    {
        error_log(
            sprintf("%s in %s::%s" . PHP_EOL, $message, debug_backtrace()[1]['file'], debug_backtrace()[1]['line']), 
            3, 
            app()->rootDir . '/logs/error.log'
        );
    }

    /**
     * This method will be called when the event is triggered.
     *
     * @return Observable
     */
    abstract public function update(mixed $eventData): Observable;
}
