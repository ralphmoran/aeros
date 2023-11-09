<?php

namespace Classes;

class Event
{
    /** @var array */
    private $events = [];

    /**
     * Adds an event listener to a given name event.
     *
     * @param string $eventName
     * @param string $observer
     * @return Event
     */
    public function addEventListener(string $eventName, string $observer): Event
    {
        if ($this->isEvent($observer)) {
            $this->events[$eventName][] = $observer;
        }

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
                (new $observer)->update($eventData);
            }
        }
    }

    /**
     * Checks if the given event is valid and extends from Classes\Observable.
     *
     * @param string $event
     * @return boolean
     */
    public function isEvent(string $event): bool
    {
        if (! class_exists($event) || ! is_subclass_of($event, Observable::class)) {
            throw new \TypeError(
                sprintf('ERROR[event] Provider "%s" were not found or invalid.', $event)
            );
        }

        return true;
    }
}
