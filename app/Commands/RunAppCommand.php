<?php

namespace Aeros\App\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunAppCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'run:app';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros run:app
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // This text will be displayed when: `$ php run:app --help`
        $this->setDescription('Runs the application. It warms up and caches the app, if option "-p" is provided.');

        $this->addOption(
            'production', 
            'p', 
            InputOption::VALUE_NONE, 
            'Option "production", alias "p". If provided, it runs warmup, cache, etc.'
        );

        $this->addOption(
            'staging', 
            's', 
            InputOption::VALUE_NONE, 
            'Option "staging", alias "s". It runs development setup on remote server.'
        );
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
        // if ($production = $input->getOption('production')) {
        //     $output->writeln(sprintf("Option 'production': %s", $production));
        // }

        // if ($staging = $input->getOption('staging')) {
        //     $output->writeln(sprintf("Option 'staging': %s", $staging));
        // }

        # TODO: List of actions to run application
        // Warm app up
        $output->writeln(sprintf('==> Warming up the application "%s"...', env('APP_NAME')));
        $returnCode = $this->getApplication()->doRun(
            new ArrayInput([
                'command' => 'run:warmup'
            ]), 
            $output
        );

        // Activate workers
        $output->writeln('==> Waking up workers...');

        $process = new Process([
            '/usr/local/bin/composer', 
            'worker-refresh'
        ]);

        $process->mustRun();
        $output->writeln($process->getOutput());

        // DB checking
        $output->writeln('==> Checking DB connections...');
        // $process = new Process([
        //     './vendor/bin/phinx', 
        //     'migrate'
        // ]);
        
        // Run DB migrations
        $output->writeln('==> Runnig DB connections...');
        // $process = new Process([
        //     './vendor/bin/phinx', 
        //     'migrate'
        // ]);

        // Cache checking
        $output->writeln('==> Checking Cache connections...');
        // $process = new Process([
        //     './vendor/bin/phinx', 
        //     'migrate'
        // ]);

        // Optimizing assets
        $output->writeln('==> Optimizing assets...');

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
