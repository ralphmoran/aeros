<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeProviderCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:provider';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:provider
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:provider --help`
        $this->setDescription('Makes a provider: `$ php aeros make:provider CreateAppDatabase`.')
            ->setHelp('Commands help...');
        
        $this->addArgument('name', InputArgument::REQUIRED, 'Argument name (required)');
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
            preg_replace('/provider$/i', '', $input->getArgument('name'))
        );

        app()->file->createFromTemplate(
            app()->basedir . '/providers/' . $classname . 'ServiceProvider.php', 
            app()->basedir . '/../src/resources/templates/provider.template', 
            [
                'classname' => $classname
            ]
        );

        return Command::SUCCESS;
    }
}
