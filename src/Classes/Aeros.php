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
        $pathCommands = [
            [
                'path' => app()->basedir . '/commands',
                'namespace' => '\App\Commands\\'
            ],
            [
                'path' => app()->basedir . '/../vendor/aeros/framework/src/Commands',
                'namespace' => '\Aeros\Src\Commands\\'
            ],
        ];

        foreach ($pathCommands as $pc) {
            foreach (scan($pc['path']) as $command) {

                require $pc['path'] . '/' . $command;

                $command = $pc['namespace'] . rtrim($command, '.php');

                app()->console->add(new $command());
            }
        }

        app()->console->run();
    }
}
