<?php

namespace Aeros\App\Events;

use Aeros\Src\Classes\Observable;

class EmailReminderEvent extends Observable
{
    public function update($eventData): bool
    {
        logger('Triggered: ' . __METHOD__, app()->basedir . '/logs/event.log');

        return true;
    }
}
