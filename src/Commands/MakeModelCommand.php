<?php

namespace Aeros\Src\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:model';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:model
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:model --help`
        $this->setDescription('Aeros REPL - "make:model" command.')
            ->setHelp('Commands help...');

        $this->addArgument('name', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Argument name (required)');
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
        foreach ($input->getArgument('name') as $classname) {

            $classname = ucfirst(
                preg_replace('/model$/i', '', $classname)
            );

            $output->write(
                sprintf(
                    '==> Creating <fg=yellow>%s</> model... ', 
                    $classname
                )
            );

            app()->file->createFromTemplate(
                app()->basedir . '/Models/' . $classname . '.php', 
                app()->basedir . '/../vendor/aeros/framework/src/resources/templates/model.template', 
                [
                    'classname' => $classname
                ]
            );

            $output->writeln('<fg=green;options=bold>OK.</>');
        }

        return Command::SUCCESS;
    }
}
