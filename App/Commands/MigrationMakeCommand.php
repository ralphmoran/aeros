<?php

namespace Aeros\App\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationMakeCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'migration:make';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros migration:make
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // This text will be displayed when: `$ php migration:make --help`
        $this->setDescription('Aeros REPL - "migration:make" command.')
            ->setHelp('Commands help...');
        
        $this->addArgument('migration', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Argument migration (required)');

        $this->addOption('seeder', null, InputOption::VALUE_OPTIONAL, 'Option "seeder", if provided, it creates the seeder');

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
        $migrations = $input->getArgument('migration');
        $phinx = app()->basedir . '/../vendor/bin/phinx';

        // Confirm if App/Database/migrations dir exists
        if (! is_dir($migrationDir = app()->basedir . '/Database/migrations')) {
            mkdir($migrationDir);
        }

        if (! is_dir($seedsDir = app()->basedir . '/Database/seeds')) {
            mkdir($seedsDir);
        }

        // Create migration
        foreach ($migrations as $migration) {

            $output->write(sprintf('==> Creating "<fg=yellow>%s</>" migration... ', $migration));

            $migrate = new Process([
                app()->basedir . '/../vendor/bin/phinx', 
                'create',
                $migration
            ]);

            $migrate->mustRun();

            $output->writeln('<fg=green;options=bold>Ok.</>');
        }

        // If seeder is given
        if ($seeder = $input->getOption('seeder')) {
            $output->write(sprintf('==> Creating "<fg=yellow>%s</>" seeder... ', $seeder));

            $migrate = new Process([
                $phinx, 
                'seed:create',
                $seeder
            ]);

            $migrate->mustRun();

            $output->writeln('<fg=green;options=bold>Ok.</>');
        }

        return Command::SUCCESS;
    }
}
