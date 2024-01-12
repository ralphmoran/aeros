<?php

namespace Aeros\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunAppCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'run:app';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros run:app
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php run:app --help`
        $this->setDescription('Aeros REPL - "run:app" command.')
            ->setHelp('Running this command will warmup generally the application.');
        
        // Adding arguments
        // InputArgument::REQUIRED
        // InputArgument::OPTIONAL
        // InputArgument::IS_ARRAY
        // $this->addArgument('name', InputArgument::REQUIRED, 'Command name (required)');

        // Adding options
        // InputOption::VALUE_NONE = 1; // Do not accept input for the option (e.g. --yell).
        // InputOption::VALUE_REQUIRED = 2; // e.g. --iterations=5 or -i5
        // InputOption::VALUE_OPTIONAL = 4; // e.g. --yell or --yell=loud
        // InputOption::VALUE_IS_ARRAY = 8; // The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
        // InputOption::VALUE_NEGATABLE = 16; // The option may have either positive or negative value (e.g. --ansi or --no-ansi).
        $this->addOption('staging', 's', InputOption::VALUE_NONE, 'Option "staging" with alias "s"');
        $this->addOption('production', 'p', InputOption::VALUE_NONE, 'Option "production" with alias "p"');
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
        // if ($name = $input->getArgument('name')) {
        //     $output->writeln(sprintf("Command name: %s", $name));
        // }

        // if ($clear = $input->getOption('clear')) {
        //     $output->writeln(sprintf("Option 'clear': %s", $clear));
        // }

        //
        // Add logic here
        //

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
