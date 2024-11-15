<?php

namespace Aeros\Src\Classes;

/**
 * This class works together with Aeros REPL.
 */
final class Aeros
{
    /**
     * It registers all custom commands that live in "./Commands" directory
     * for further execution based on which command is called.
     * 
     * @see /aeros
     * @return void
     */
    public function registerCommands()
    {
        $path = app()->basedir . '/commands';

        // Load all command classes from ./commands folder
        foreach (scan($path) as $command) {

            require $path . '/' . $command;

            $command = '\App\Commands\\' . rtrim($command, '.php');

            app()->console->add(new $command());
        }

        app()->console->run();
    }
}
