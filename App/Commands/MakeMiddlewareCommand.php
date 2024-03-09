<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:middleware';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:middleware
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:middleware --help`
        $this->setDescription('Aeros REPL - "make:middleware" command.')
            ->setHelp('Commands help...');

        $this->addArgument('name', InputArgument::REQUIRED, 'Middleware name');
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
        $classname = ucfirst(
            preg_replace('/middleware$/i', '', $input->getArgument('name'))
        );

        app()->file->createFromTemplate(
            app()->basedir . '/middlewares/' . $classname . 'Middleware.php', 
            app()->basedir . '/../src/resources/templates/middleware.template', 
            [
                'classname' => $classname
            ]
        );

        return Command::SUCCESS;
    }
}
