<?php

namespace Aeros\Events;

use Aeros\Lib\Classes\Observable;

class EmailNotifierEvent extends Observable
{
    public function update($eventData): bool
    {
        logger(
            'From: ' . __CLASS__ . '::' . __LINE__ . '. Payload: ' . json_encode($eventData),
            app()->basedir . '/logs/event.log'
        );

        return true;
    }
}
