<?php

namespace Events;

use Classes\Observable;

class EmailNotifier extends Observable
{
    public function update($eventData): Observable
    {
        echo 'From: ' . __CLASS__ . '::' . __LINE__ . '::' . json_encode($eventData) . PHP_EOL;

        return $this;
    }
}
