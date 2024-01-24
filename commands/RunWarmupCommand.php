<?php

namespace Aeros\Commands;

use Aeros\Lib\Classes\Job;
use Aeros\Lib\Classes\Cron;
use Aeros\Lib\Classes\Worker;
use Aeros\Lib\Classes\Observable;
use Aeros\Lib\Classes\ServiceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunWarmupCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'run:warmup';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros run:warmup
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php run:warmup --help`
        $this->setDescription('Aeros REPL - "run:warmup" command.')
            ->setHelp('Running this command will warmup generally the application.');
        
        // Adding arguments
        // InputArgument::REQUIRED
        // InputArgument::OPTIONAL
        // InputArgument::IS_ARRAY
        // $this->addArgument('name', InputArgument::REQUIRED, 'Command name (required)');

        // Adding options
        // InputOption::VALUE_NONE = 1; // Do not accept input for the option (e.g. --yell).
        // InputOption::VALUE_REQUIRED = 2; // e.g. --iterations=5 or -i5
        // InputOption::VALUE_OPTIONAL = 4; // e.g. --yell or --yell=loud
        // InputOption::VALUE_IS_ARRAY = 8; // The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
        // InputOption::VALUE_NEGATABLE = 16; // The option may have either positive or negative value (e.g. --ansi or --no-ansi).
        $this->addOption('staging', 's', InputOption::VALUE_NONE, 'Option "staging" with alias "s"');
        $this->addOption('production', 'p', InputOption::VALUE_NONE, 'Option "production" with alias "p"');
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
        // if ($staging = $input->getOption('staging')) {
        //     $output->writeln(sprintf("Option 'staging': %s", $staging));
        // }
        
        // if ($production = $input->getOption('production')) {
        //     $output->writeln(sprintf("Option 'production': %s", $production));
        // }

        // Get all from config('app.warmup')
        // Check the parent class: ServiceProvider, Cron, Job, Worker, etc. 
        // Each one has a specific method to run the main logic
        $warmups = config('app.warmup');

        $progressBar = new ProgressBar($output, count($warmups));
        $progressBar->setFormatDefinition(
            'warmup', 
            " %current%/%max% [%bar%] %message% %percent:3s%% %elapsed:6s%/%estimated:-6s%\n"
        );
        $progressBar->setFormat('warmup');
        $progressBar->setMessage('Start');

        $progressBar->start();

        foreach ($warmups as $warmup) {
            
            if (class_exists($warmup)) {

                $progressBar->setMessage('Warming up: ' . $warmup);

                // Service providers
                if (is_subclass_of($warmup, ServiceProvider::class)) {
                    (new $warmup)->boot();
                }

                // Workers
                if (is_subclass_of($warmup, Worker::class)) {
                    (new $warmup)->handle();
                }

                // Crons
                if (is_subclass_of($warmup, Cron::class)) {
                    (new $warmup)->work();
                }

                // Events
                if (is_subclass_of($warmup, Observable::class)) {
                    (new $warmup)->update();
                }

                // Jobs
                if (is_subclass_of($warmup, Job::class)) {
                    (new $warmup)->doWork();
                }

                $progressBar->advance();
            }
        }

        $progressBar->setMessage('Warmup completed.');
        $progressBar->finish();

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
