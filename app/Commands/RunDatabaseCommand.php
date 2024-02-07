<?php

namespace Aeros\App\Commands;

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
        // Add logic here
        if (empty(env('DB_DATABASE')) || $input->getOption('create')) {

            $development = $input->getOption('development');
            $staging = $input->getOption('staging');
            $production = $input->getOption('production');

            // All DB names were provided
            if ($development && $staging && $production) {

                $this->verifyDBConnection($staging, 'staging', $output);
                $this->verifyDBConnection($production, 'production', $output);
                $this->verifyDBConnection($development, 'development', $output);

                return Command::SUCCESS;
            }

            $helper = $this->getHelper('question');
            $question = new Question('<fg=yellow>Enter your database name:</> ');

            if ($database = $helper->ask($input, $output, $question)) {

                $development = null;
                $staging = null;
                $production = null;

                if ($input->getOption('all')) {
                    $development = $database . '_development';
                    $staging = $database . '_staging';
                    $production = $database . '_production';
                }

                // Staging
                if ($staging || $staging = $input->getOption('staging')) {
                    $this->verifyDBConnection($staging, 'staging', $output);
                }

                // Production
                if ($production || $production = $input->getOption('production')) {
                    $this->verifyDBConnection($production, 'production', $output);
                }
                
                // Development
                if ($development || $development = $input->getOption('development')) {
                    $this->verifyDBConnection($development, 'development', $output);
                }

                return Command::SUCCESS;
            }

            $output->writeln('<bg=red>Error: No database name was provided</>');

            return Command::FAILURE;
        }
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

        $dbh = new \PDO("mysql:host=" . $defaultDBSetup['server'], $defaultDBSetup['username'], $defaultDBSetup['password']);

        if ($dbh->exec("CREATE DATABASE IF NOT EXISTS `$database`;") === false) {
            print_r($dbh->errorInfo(), true);
            return Command::FAILURE;
        }

        // Updating env and phinx files
        $phinxKey = strtolower($env);

        if (! updateEnvVariable(['DB_DATABASE' => $database])) {
            $output->writeln(sprintf("<bg=red>Error creating: %s</> ", $database));
            return;
        }

        if (! file_exists(app()->basedir . '/../phinx.json')) {
            app()->file->createFromTemplate(
                app()->basedir . '/../phinx.json',
                app()->basedir . '/../src/resources/templates/phinx.template'
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
