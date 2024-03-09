<?php

namespace App\Events;

use Aeros\Src\Classes\Observable;

class EmailFollowupEvent extends Observable
{
    public function update($eventData): bool
    {
        // echo 'From: ' . __CLASS__ . '::' . __LINE__ . '::' . json_encode($eventData);
        // echo "\n";

        return true;
    }
}
