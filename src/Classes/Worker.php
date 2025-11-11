<?php

namespace Aeros\Src\Classes;

abstract class Worker
{
    /**
     * This method will be called when the worker is started.
     *
     * @return void
     */
    abstract public function handle();

    /**
     * Starts the worker in an infinite loop.
     *
     * When using blocking operations (BLPOP), sleep is not needed
     * as Redis handles the waiting efficiently.
     *
     * @param   Callable|null   $job
     * @param   mixed           $args Mixed arguments to pass to the callable, if provided
     * @param   integer         $sleep Sleep time in microseconds (ignored when using blocking ops)
     * @return  void
     */
    public function start(?Callable $callable = null, mixed $args = null, int $sleep = 0)
    {
        // Only on CLI
        if (isMode('cli')) {
            while (true) {
                if (is_null($callable)) {
                    $this->handle();
                }

                if (is_callable($callable)) {
                    $callable($args);
                }

                // Only sleep if specified and > 0
                // When using blocking operations, this is unnecessary
                if ($sleep > 0) {
                    usleep($sleep);
                }
            }

            return;
        }

        // On web server, only runs once.
        // This action protects to infinite run on web calls.
        if (is_null($callable)) {
            $this->handle();
        }

        if (is_callable($callable)) {
            $callable($args);
        }
    }
}