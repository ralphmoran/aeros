<?php

namespace Aeros\App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeEventCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:event';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:event
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:event --help`
        $this->setDescription('Aeros REPL - "make:event" command.')
            ->setHelp('Commands help...');
        
        // Adding arguments
        $this->addArgument('name', InputArgument::REQUIRED, 'Argument event name (required)');
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
        if ($name = $input->getArgument('name')) {

            app()->file->createFromTemplate(
                app()->basedir . '/events/' . ucfirst($name) . 'Event.php', 
                app()->basedir . '/../src/resources/templates/event.template', 
                [
                    'classname' => ucfirst($name),
                ]
            );

            return Command::SUCCESS;
        }

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::INVALID;
    }
}
