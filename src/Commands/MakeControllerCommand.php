<?php

namespace Aeros\Src\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:controller';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:controller CommandName
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:controller --help`
        $this->setDescription('Aeros REPL - "make:controller" command. It creates a controller blueprint file.');
        
        $this->addArgument('name', InputArgument::REQUIRED, 'Controller name (required)');
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
        $classname = preg_replace('/controller$/i', '', ucfirst($input->getArgument('name')));

        app()->file->createFromTemplate(
            app()->basedir . '/controllers/' . $classname . 'Controller.php', 
            app()->basedir . '/../vendor/aeros/framework/src/resources/templates/controller.template', 
            [
                'classname' => $classname
            ]
        );

        return Command::SUCCESS;
    }
}
