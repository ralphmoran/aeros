<?php

namespace Classes;

class EventDispatcher
{
    /** @var array */
    private $events = [];

    /**
     * Adds an event listener to a given name event.
     *
     * @param string $eventName
     * @param Observable $observer
     * @return EventDispatcher
     */
    public function addEventListener(string $eventName, Observable $observer): EventDispatcher
    {
        $this->events[$eventName][] = $observer;

        return $this;
    }

    /**
     * Triggers a collection of events based on the given name.
     *
     * @param string $eventName
     * @param mixed $eventData
     * @return void
     */
    public function emit(string $eventName, mixed $eventData = '')
    {
        if (isset($this->events[$eventName])) {

            foreach ($this->events[$eventName] as $observer) {

                $observer->update($eventData);

                if (env('APP_DEBUG')) {
                    logger(
                        "Event `{$eventName}` triggered in " . __CLASS__ . '::' . __LINE__,
                        app()->basedir . '/logs/event.log'
                    );
                }
            }
        }
    }
}
