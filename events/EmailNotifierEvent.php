<?php

namespace Aeros\Events;

use Aeros\Lib\Classes\Observable;

class EmailNotifierEvent extends Observable
{
    public function update($eventData): Observable
    {
        // echo 'From: ' . __CLASS__ . '::' . __LINE__ . '::' . json_encode($eventData) . PHP_EOL;
        // echo "\n";

        return $this;
    }
}
