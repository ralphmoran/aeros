<?php

namespace Events;

use Classes\Observable;

/**
 * Reusable event listener for queue system.
 */

class QueueEvent extends Observable
{
    //
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function update($eventData): Observable
    {
        echo 'From: ' . __CLASS__ . '::' . __LINE__ . '::' . json_encode($eventData) . PHP_EOL;
        echo 'Data: ' . $this->data . PHP_EOL;
        echo "\n";

        return $this;
    }
}
