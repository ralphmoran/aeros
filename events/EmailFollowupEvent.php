<?php

namespace Aeros\Events;

use Aeros\Lib\Classes\Observable;

class EmailFollowupEvent extends Observable
{
    public function update($eventData): Observable
    {
        // echo 'From: ' . __CLASS__ . '::' . __LINE__ . '::' . json_encode($eventData);
        // echo "\n";

        return $this;
    }
}
