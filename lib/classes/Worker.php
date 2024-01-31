<?php

namespace Aeros\Lib\Classes;

abstract class Worker
{
    abstract public function handle();

    /**
     * Starts the worker in an infinite loop.
     *
     * @param Callable|null $job
     * @param mixed $args Mixed arguments to pass to the callable, if provided
     * @param integer $sleep Sleep time
     * @return void
     */
    public function start(?Callable $callable = null, mixed $args = null, int $sleep = 5) 
    {
        // Only on CLI
        if (strpos(PHP_SAPI, 'cli') !== false) {
            while (true) {
                if (is_null($callable)) {
                    $this->handle();
                }

                if (is_callable($callable)) {
                    $callable($args);
                }

                sleep($sleep);
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
