<?php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'test:one';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros test:one routeName --clear|-c
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php test:one --help`
        $this->setDescription('Test command description');
        
        // Adding arguments
        // InputArgument::REQUIRED
        // InputArgument::OPTIONAL
        // InputArgument::IS_ARRAY
        $this->addArgument('name', InputArgument::OPTIONAL, 'Custom argument. This is the variable name');

        // Adding options
        // InputOption::VALUE_NONE = 1; // Do not accept input for the option (e.g. --yell).
        // InputOption::VALUE_REQUIRED = 2; // e.g. --iterations=5 or -i5
        // InputOption::VALUE_OPTIONAL = 4; // e.g. --yell or --yell=loud
        // InputOption::VALUE_IS_ARRAY = 8; // The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
        // InputOption::VALUE_NEGATABLE = 16; // The option may have either positive or negative value (e.g. --ansi or --no-ansi).
        $this->addOption('clear', 'c', InputOption::VALUE_NONE, 'Option "clear" with alias "c"');
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
            $output->writeln(sprintf("Argument 'name': %s", $name));
        }

        if ($clear = $input->getOption('clear')) {
            $output->writeln(sprintf("Option 'clear': %s", $clear));
        }

        //
        // Add logic here
        //
        $output->writeln("Command: " . __CLASS__);

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
