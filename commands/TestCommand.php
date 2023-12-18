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
     *
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php test:one --help`
        $this->setDescription('Test command description');
        
        // Adding arguments
        $this->addArgument('name', InputArgument::OPTIONAL, 'Custom argument. This is the variable name');

        // Adding options
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

        // Add logic here

        return Command::SUCCESS;
    }
}
