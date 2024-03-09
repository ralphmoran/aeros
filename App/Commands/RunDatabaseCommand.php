<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunDatabaseCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'run:database';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros run:database
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php run:database --help`
        $this->setDescription('Aeros REPL - "run:database" command.')
            ->setHelp('Commands help...');

        $this->addOption(
            'create', 
            'c', 
            InputOption::VALUE_NONE, 'Option "create" with alias "c", if provided, the database will be generated'
        );

        $this->addOption(
            'seed', 
            null, 
            InputOption::VALUE_NONE, 'Option "seed", if provided, all seeds will be generated'
        );

        $this->addOption(
            'development', 
            'd', 
            InputOption::VALUE_OPTIONAL, 'Option "development" with alias "d", if provided, it will create development DB'
        );
        
        $this->addOption(
            'staging', 
            's', 
            InputOption::VALUE_OPTIONAL, 'Option "staging" with alias "s", if provided, it will create Staging DB'
        );

        $this->addOption(
            'production', 
            'p', 
            InputOption::VALUE_OPTIONAL, 'Option "production" with alias "p", if provided, it will create Production DB'
        );
        
        $this->addOption(
            'all', 
            'a', 
            InputOption::VALUE_NONE, 'Option "all" with alias "a", if provided, it will create all env DBs'
        );
    }

    /**
     * Sets the input and gets the out of current command.
     *
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @return  void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (($production = $input->getOption('production')) && $input->getOption('create') && ! $input->getOption('all')) {
            $this->verifyDBConnection($production, 'production', $output);
        }

        if (($staging = $input->getOption('staging')) && $input->getOption('create') && ! $input->getOption('all')) {
            $this->verifyDBConnection($staging, 'staging', $output);
        }

        if (($development = $input->getOption('development')) && $input->getOption('create') && ! $input->getOption('all')) {
            $this->verifyDBConnection($development, 'development', $output);
        }

        // Add logic here
        if ($input->getOption('all') && $input->getOption('create')) {

            $helper = $this->getHelper('question');
            $question = new Question('<fg=yellow>Enter your database name:</> ');

            if ($database = $helper->ask($input, $output, $question)) {

                $this->verifyDBConnection($database . '_production', 'production', $output);
                $this->verifyDBConnection($database . '_staging', 'staging', $output);
                $this->verifyDBConnection($database, 'development', $output);

                return Command::SUCCESS;
            }

            $output->writeln('<bg=red>Error: No database name was provided</>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Verifies and updates the database connection configuration.
     *
     * @param string           $database The name of the database. Defaults to 'aeros_default'.
     * @param string           $env      The environment. Defaults to 'development'.
     * @param OutputInterface $output   The output interface for writing messages.
     *
     * @return void
     */
    private function verifyDBConnection(string $database = 'aeros_default', string $env = 'development', OutputInterface $output) 
    {
        // Testing DB connection
        $defaultDBSetup = config('db.connections')[implode(config('db.default'))];

        // Create DB
        if (db('none')->exec("CREATE DATABASE IF NOT EXISTS `$database`;") === false) {
            print_r(db('none')->errorInfo(), true);

            return Command::FAILURE;
        }

        // Updating env and phinx files
        $phinxKey = strtolower($env);

        if (! updateEnvVariable(['DB_DATABASE' => $database])) {
            $output->writeln(sprintf("<bg=red>Error creating: %s</> ", $database));
            return;
        }

        // Validates if phinx.json exists
        if (! file_exists(app()->basedir . '/../phinx.json')) {
            app()->file->createFromTemplate(
                app()->basedir . '/../phinx.json',
                app()->basedir . '/../src/resources/templates/phinx.template',
                $defaultDBSetup
            );
        }

        if (! updateJsonNode(['environments.' . $phinxKey . '.name' => $database], app()->basedir . '/../phinx.json')) {
            $output->writeln(sprintf("<bg=red>Error creating: %s</> ", $database));
            return;
        }

        $output->writeln(
            sprintf("Database created: <fg=green;options=bold>%s</> ", $database)
        );
    }
}
