<?php

namespace Aeros\Src\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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
            'e',
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
            $this->setupDbByName($production, 'production', $output);
        }

        if (($staging = $input->getOption('staging')) && $input->getOption('create') && ! $input->getOption('all')) {
            $this->setupDbByName($staging, 'staging', $output);
        }

        if (($development = $input->getOption('development')) && $input->getOption('create') && ! $input->getOption('all')) {
            $this->setupDbByName($development, 'development', $output);
        }

        // Add logic here
        if ($input->getOption('all') && $input->getOption('create')) {

            $helper = $this->getHelper('question');
            $question = new Question('<fg=yellow>Enter your database name:</> ');

            if ($database = $helper->ask($input, $output, $question)) {

                $this->setupDbByName($database . '_production', 'production', $output);
                $this->setupDbByName($database . '_staging', 'staging', $output);
                $this->setupDbByName($database, 'development', $output);

                return Command::SUCCESS;
            }

            $output->writeln('<bg=red>Error: No database name was provided</>');

            return Command::FAILURE;
        }

        if ($input->getOption('seed') && $input->getOption('all')) {
            $seeders = new Process([
                './vendor/bin/phinx',
                'seed:run'
            ]);

            $seeders->mustRun();
            $output->write('<fg=green>'. $seeders->getOutput() . '</>');
            $output->writeln('... <fg=green;options=bold>OK.</>');
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
    private function setupDbByName(string $database = 'aeros_db', string $env = 'development', OutputInterface $output)
    {
        // Testing DB connection
        $defaultDBSetup = config('db.connections')[implode(config('db.default'))];

        // Get the driver name from PDO
        $driver = db()->getDBConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        // Create database with driver-specific syntax
        try {
            switch ($driver) {
                case 'pgsql': // PostgreSQL
                    $this->createPostgresDatabase($database, $output);
                    break;

                case 'mysql': // MySQL/MariaDB
                    $identifier = "`$database`";
                    $sql = "CREATE DATABASE IF NOT EXISTS $identifier";
                    db()->exec($sql);
                    break;

                case 'sqlite': // SQLite
                    // SQLite databases are files, no CREATE DATABASE needed
                    // The file is created when you connect to it
                    $output->writeln("<fg=yellow>SQLite: Database file will be created on first connection</>");
                    break;

                case 'sqlsrv': // Microsoft SQL Server
                case 'dblib': // FreeTDS
                    $identifier = "[$database]";
                    $sql = "IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = N'$database') CREATE DATABASE $identifier";
                    db()->exec($sql);
                    break;

                default:
                    $output->writeln("<bg=red>Unsupported database driver: $driver</>");
                    return Command::FAILURE;
            }

            $output->writeln(sprintf("<fg=green>✓</> Database operation completed for: <fg=cyan>%s</> (driver: %s)", $database, $driver));

        } catch (\PDOException $e) {
            // Check if error is "database already exists"
            if ($this->isDatabaseExistsError($e, $driver)) {
                $output->writeln(sprintf("<fg=yellow>!</> Database already exists: <fg=cyan>%s</>", $database));
            } else {
                $output->writeln(
                    sprintf(
                        "<bg=red>Error creating database '%s':</> %s",
                        $database,
                        $e->getMessage()
                    )
                );
                return Command::FAILURE;
            }
        }

        // Updating env and phinx files
        $phinxKey = strtolower($env);

        if (! updateEnvVariable(['DB_DATABASE' => $database])) {
            $output->writeln(sprintf("<bg=red>Error updating .env for: %s</> ", $database));
            return;
        }

        // Validates if phinx.json exists
        if (! file_exists(app()->basedir . '/../phinx.json')) {
            app()->file->createFromTemplate(
                app()->basedir . '/../phinx.json',
                app()->basedir . '/../vendor/aeros/framework/src/resources/templates/phinx.template',
                $defaultDBSetup
            );
        }

        if (! updateJsonNode(['environments.' . $phinxKey . '.name' => $database], app()->basedir . '/../phinx.json')) {
            $output->writeln(sprintf("<bg=red>Error updating phinx.json for: %s</> ", $database));
            return;
        }

        $output->writeln(
            sprintf("Database configured: <fg=green;options=bold>%s</> ", $database)
        );
    }

    /**
     * Creates a PostgreSQL database (handles lack of IF NOT EXISTS support)
     *
     * @param string          $database The database name
     * @param OutputInterface $output   The output interface
     * @return void
     * @throws \PDOException
     */
    private function createPostgresDatabase(string $database, OutputInterface $output): void
    {
        // PostgreSQL doesn't support IF NOT EXISTS in older versions
        // First check if database exists
        $stmt = db()->prepare("SELECT 1 FROM pg_database WHERE datname = :database")
            ->execute(['database' => $database]);

        if ($stmt->fetchColumn()) {
            $output->writeln(sprintf("<fg=yellow>!</> Database already exists: <fg=cyan>%s</>", $database));
            return;
        }

        // Use double quotes for identifiers in PostgreSQL
        $identifier = '"' . str_replace('"', '""', $database) . '"';
        db()->exec(
            "CREATE DATABASE $identifier"
        );
    }

    /**
     * Checks if PDO exception is a "database already exists" error
     *
     * @param \PDOException $e      The exception
     * @param string        $driver The database driver
     * @return bool
     */
    private function isDatabaseExistsError(\PDOException $e, string $driver): bool
    {
        $errorCode = $e->getCode();
        $errorMessage = strtolower($e->getMessage());

        return match($driver) {
            'pgsql' => $errorCode === '42P04' || str_contains($errorMessage, 'already exists'),
            'mysql' => $errorCode === '1007' || str_contains($errorMessage, 'database exists'),
            'sqlsrv', 'dblib' => str_contains($errorMessage, 'already exists'),
            default => false,
        };
    }
}
