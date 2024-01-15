<?php

namespace Aeros\Commands;

use Aeros\Lib\Classes\Cron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Main class that is called by main cron from crontab.
 * 
 * This is call like this:
 * 
 *      * * * * * /usr/bin/php /var/www/html/aeros scheduler 1>> /var/www/html/logs/error.log 2>&1
 */
class SchedulerCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'scheduler';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros scheduler
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php scheduler --help`
        $this->setDescription('Aeros REPL - "scheduler" command. It runs all scheduled crons.');
    }

    /**
     * Sets the input and gets the out of current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = app()->basedir . '/queues/crons';

        foreach (scan($path) as $cron) {
            require $path . '/' . $cron;

            $cron = '\\Aeros\\Queues\\Crons\\' . rtrim($cron, '.php');

            if (($cronInstance = new $cron()) instanceof Cron) {
                $cronInstance->run();
            }
        }

        app()->scheduler->run();

        return Command::SUCCESS;
    }
}
