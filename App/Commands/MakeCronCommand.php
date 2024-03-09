<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCronCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:cron';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:cron
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:cron --help`
        $this->setDescription('Aeros REPL - "make:cron" command.');

        $this->addArgument('name', InputArgument::REQUIRED, 'Cron name (required)');
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
        app()->file->createFromTemplate(
            app()->basedir . '/queues/crons/' . ucfirst($input->getArgument('name')) . 'Cron.php', 
            app()->basedir . '/../src/resources/templates/cron.template', 
            [
                'classname' => ucfirst($input->getArgument('name')),
            ]
        );

        return Command::SUCCESS;
    }
}
