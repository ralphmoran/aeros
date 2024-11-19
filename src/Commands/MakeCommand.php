<?php

namespace Aeros\Src\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:command';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:command CommandName
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:command --help`
        $this->setDescription('Aeros REPL - "make:command" command. It creates a command blueprint file.');
        
        // Adding arguments
        // InputArgument::REQUIRED
        // InputArgument::OPTIONAL
        // InputArgument::IS_ARRAY
        $this->addArgument('name', InputArgument::REQUIRED, 'Command name (required)');

        // Adding options
        // InputOption::VALUE_NONE = 1; // Do not accept input for the option (e.g. --yell).
        // InputOption::VALUE_REQUIRED = 2; // e.g. --iterations=5 or -i5
        // InputOption::VALUE_OPTIONAL = 4; // e.g. --yell or --yell=loud
        // InputOption::VALUE_IS_ARRAY = 8; // The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
        // InputOption::VALUE_NEGATABLE = 16; // The option may have either positive or negative value (e.g. --ansi or --no-ansi).
        // $this->addOption('clear', 'c', InputOption::VALUE_NONE, 'Option "clear" with alias "c"');
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
        $name = preg_replace('/command$/i', '', $input->getArgument('name'));

        $classname = ucfirst($name);
        $command = implode(':', preg_split('/(?=[A-Z])/', lcfirst($classname)));

        app()->file->createFromTemplate(
            app()->basedir . '/commands/' . $classname . 'Command.php', 
            app()->basedir . '/../vendor/aeros/framework/src/resources/templates/command.template', 
            [
                'classname' => $classname,
                'name' => strtolower($command)
            ]
        );

        return Command::SUCCESS;
    }
}
