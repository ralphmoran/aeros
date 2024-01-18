<?php

namespace Aeros\Events;

use Aeros\Lib\Classes\Observable;

class EmailReminderEvent extends Observable
{
    public function update($eventData): bool
    {
        // echo 'From: ' . __CLASS__ . '::' . __LINE__ . '::' . json_encode($eventData) . PHP_EOL;
        // echo "\n";

        return true;
    }
}
