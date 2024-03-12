<?php

namespace App\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeSeedCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:seed';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:seed
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:seed --help`
        $this->setDescription('Aeros REPL - "make:seed" command.')
            ->setHelp('Commands help...');

        $this->addArgument('seeder', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Argument seeder (required)');
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
        if (! is_dir($seedsDir = app()->basedir . '/Database/seeds')) {
            mkdir($seedsDir);
        }

        foreach ($input->getArgument('seeder') as $seeder) {

            $output->write(sprintf('==> Creating "<fg=yellow>%s</>" seeder... ', $seeder));

            $migrate = new Process([
                app()->basedir . '/../vendor/bin/phinx', 
                'seed:create',
                $seeder
            ]);

            $migrate->mustRun();

            $output->writeln('<fg=green;options=bold>Ok.</>');
        }

        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
