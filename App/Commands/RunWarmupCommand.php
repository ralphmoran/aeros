<?php

namespace Aeros\App\Commands;

use Aeros\Src\Classes\Job;
use Aeros\Src\Classes\Cron;
use Aeros\Src\Classes\Worker;
use Aeros\Src\Classes\Observable;
use Aeros\Src\Classes\ServiceProvider;
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

        $progressBar->setMessage('<fg=green;options=bold>Warmup completed.</>');
        $progressBar->finish();

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
