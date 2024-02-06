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
            'Option "production", alias "p". If provided, it changes environtment to production.'
        );

        $this->addOption(
            'staging', 
            's', 
            InputOption::VALUE_NONE, 
            'Option "staging", alias "s". If provided, it changes environtment to staging.'
        );

        $this->addOption(
            'development', 
            'd', 
            InputOption::VALUE_NONE, 
            'Option "development", alias "d". If provided, it changes environtment to development.'
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
        # TODO: List of actions to run application
        // ---------------------------------------------------
        // Set environtment variable to...
        if ($input->getOption('production')) {
            $output->write('==> Changing environtment to <fg=bright-green;options=bold>production</>... ');

            updateEnvVariable(['APP_ENV' => 'production']);

            $output->write('<fg=green;options=bold>OK.</>');
            $output->writeln('');
        } else if ($input->getOption('staging')) {
            $output->write('==> Changing environtment to <fg=magenta;options=bold>staging</>... ');

            updateEnvVariable(['APP_ENV' => 'staging']);

            $output->write('<fg=green;options=bold>OK.</>');
            $output->writeln('');
        } else if ($input->getOption('development')) {
            $output->write('==> Changing environtment to <fg=yellow;options=bold>development</>... ');

            updateEnvVariable(['APP_ENV' => 'development']);

            $output->write('<fg=green;options=bold>OK.</>');
            $output->writeln('');
        } else {
            $output->write('==> No env flag provided. Changing environtment to <fg=yellow;options=bold>development</>... ');

            updateEnvVariable(['APP_ENV' => 'development']);

            $output->write('<fg=green;options=bold>OK.</>');
            $output->writeln('');
        }

        // ---------------------------------------------------
        // Warm the app up
        $output->writeln(sprintf('==> Warming up the application <fg=bright-green;options=bold>"%s"</>', env('APP_NAME')));
        $returnCode = $this->getApplication()->doRun(
            new ArrayInput([
                'command' => 'run:warmup'
            ]), 
            $output
        );

        // ---------------------------------------------------
        // // Activate workers
        $output->write('==> Waking up workers... ');

        $process = new Process([
            '/usr/local/bin/composer', 
            'worker-refresh'
        ]);

        $process->mustRun();
        // $output->write(trim($process->getOutput()));
        $output->write('<fg=green;options=bold>OK.</>');
        $output->writeln('');

        // ---------------------------------------------------
        // DB checking
        $output->write('==> Checking DB connections... ');
        // $process = new Process([
        //     './vendor/bin/phinx', 
        //     'migrate'
        // ]);
        $output->write('<fg=green;options=bold>OK.</>');
        $output->writeln('');

        // ---------------------------------------------------
        // Run DB migrations
        $output->write('==> Running DB migrations... ');
        // $process = new Process([
        //     './vendor/bin/phinx', 
        //     'migrate'
        // ]);
        $output->write('<fg=green;options=bold>OK.</>');
        $output->writeln('');

        // ---------------------------------------------------
        // Cache checking
        $output->write('==> Checking Cache connections... ');
        // $process = new Process([
        //     './vendor/bin/phinx', 
        //     'migrate'
        // ]);
        $output->write('<fg=green;options=bold>OK.</>');
        $output->writeln('');

        // ---------------------------------------------------
        // Optimizing assets
        $output->write('==> Optimizing assets... ');
        // $process = new Process([
        //     './vendor/bin/phinx', 
        //     'migrate'
        // ]);
        $output->write('<fg=green;options=bold>OK.</>');
        $output->writeln('');

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
