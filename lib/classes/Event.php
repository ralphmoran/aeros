<?php

namespace Aeros\Lib\Classes;

class Event
{
    /**
     * Adds an event listener to a given name event.
     *
     * @param string $eventName
     * @param string $observer
     * @return Event
     */
    public function addEventListener(string $eventName, string $observer): Event
    {
        $this->isEvent($observer);

        if (! cache()->exists($eventName)) {
            cache()->set($eventName, $observer);
        }

        return $this;
    }

    /**
     * Triggers a collection of events based on the given name.
     *
     * @param string $eventName
     * @param mixed $eventData
     * @param bool $deleteEvent
     * @return bool
     * @throws \TypeError
     */
    public function emit(string $eventName, mixed $eventData = '', bool $deleteEvent = false): bool
    {
        if ($observer = cache()->get($eventName)) {

            (new $observer)->update($eventData);

            if ($deleteEvent) {
                cache()->del($eventName);
            }

            return true;
        }

        throw new \TypeError(
            sprintf('ERROR[event] Event "%s" does not exist.', $eventName)
        );
    }

    /**
     * Checks if the given event is valid and extends from Classes\Observable.
     *
     * @param string $event
     * @return boolean
     * @throws \TypeError
     */
    public function isEvent(string $event): bool
    {
        if (! class_exists($event) || ! is_subclass_of($event, Observable::class)) {
            throw new \TypeError(
                sprintf('ERROR[event] Event "%s" were not found or invalid.', $event)
            );
        }

        return true;
    }
}
