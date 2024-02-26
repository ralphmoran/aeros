<?php

namespace Aeros\App\Events;

use Aeros\Src\Classes\Observable;

/**
 * Reusable event listener for queue system.
 * 
 * Special class that accepts dynamic values when event label is triggered.
 */

class QueueEvent extends Observable
{
    /**
     * On instantiation, this special event can process other events, objects,
     * and values.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Takes care of queue system.
     *
     * @param mixed $eventData
     * @return bool
     */
    public function update($eventData): bool
    {
        logger(
            $this->data, 
            app()->basedir . '/logs/event.log'
        );

        return true;
    }
}
