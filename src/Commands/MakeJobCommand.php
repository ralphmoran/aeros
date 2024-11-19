<?php

namespace Aeros\Src\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeJobCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:job';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:job
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:job --help`
        $this->setDescription('Aeros REPL - "make:job" command.')
            ->setHelp('Commands help...');
        
        // Adding arguments
        $this->addArgument('name', InputArgument::REQUIRED, 'Argument "name" (required)');
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
            app()->basedir . '/queues/jobs/' . ucfirst($input->getArgument('name')) . 'Job.php', 
            app()->basedir . '/../vendor/aeros/framework/src/resources/templates/job.template', 
            [
                'classname' => ucfirst($input->getArgument('name')),
            ]
        );

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
