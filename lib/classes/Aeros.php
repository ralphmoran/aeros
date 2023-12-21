<?php

namespace Classes;

final class Aeros
{
    /**
     * Registers all commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $path = dirname(dirname(__DIR__)) . '/commands';

        // Load all command classes from ./commands folder
        foreach (scan($path) as $command) {

            require $path . '/' . $command;

            $command = '\Commands\\' . rtrim($command, '.php');

            app()->console->add(new $command());
        }

        app()->console->run();
    }
}
