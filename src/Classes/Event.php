<?php

namespace Aeros\Src\Classes;

class Event
{
    /**
     * Adds an event listener to a given name event.
     *
     * @param string $eventId
     * @param string $event
     * @return Event
     */
    public function addEventListener(string|array $eventId, string $event): Event
    {
        $this->isEvent($event);

        cache('memcached')->add($eventId, $event);

        return $this;
    }

    /**
     * Triggers a collection of events based on the given name.
     *
     * @param string $eventId
     * @param mixed $eventData
     * @param bool $deleteEvent
     * @return bool
     * @throws \TypeError
     */
    public function emit(string $eventId, mixed $eventData = '', bool $deleteEvent = false): bool
    {
        if ($observer = cache('memcached')->get($eventId)) {

            (new $observer)->update($eventData);

            if ($deleteEvent) {
                cache('memcached')->delete($eventId);
            }

            return true;
        }

        return false;
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
