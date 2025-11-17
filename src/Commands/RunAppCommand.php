<?php

namespace Aeros\Src\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
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
        // ---------------------------------------------------
        // Set environtment variable to...
        if ($input->getOption('production')) {
            $output->write('==> Changing environtment to <fg=bright-green;options=bold>production</>... ');

            updateEnvVariable(['APP_ENV' => 'production']);

            $output->writeln('<fg=green;options=bold>OK.</>');
        } else if ($input->getOption('staging')) {
            $output->write('==> Changing environtment to <fg=magenta;options=bold>staging</>... ');

            updateEnvVariable(['APP_ENV' => 'staging']);

            $output->writeln('<fg=green;options=bold>OK.</>');
        } else if ($input->getOption('development')) {
            $output->write('==> Changing environtment to <fg=yellow>development</>... ');

            updateEnvVariable(['APP_ENV' => 'development']);

            $output->writeln('<fg=green;options=bold>OK.</>');
        } else {
            $output
                ->write('==> <fg=yellow>No env flag provided. </>Changing environtment to <fg=yellow;options=bold>development</>... ');

            updateEnvVariable(['APP_ENV' => 'development']);

            $output->writeln('<fg=green;options=bold>OK.</>');
        }

        // ---------------------------------------------------
        // DB checking
        $output->write(
            sprintf(
                '==> Checking default <fg=yellow>%s</> DB connection status... ',
                implode(config('db.default'))
            )
        );

        if (db()->ping() === false) {
            $output->writeln('<bg=red;options=bold>Error</> There was a problem connecting to the DB server');

            return Command::FAILURE;
        }

        $output->writeln('<fg=green;options=bold>Ok.</>');

        // ---------------------------------------------------
        // Run DB migrations
        $output->writeln('==> Running DB migrations... ');

        if (! file_exists(app()->basedir . '/../phinx.json')) {
            $output->writeln(
                '==> <bg=red;options=bold>Error</> <fg=yellow>phinx.json</> file does not exist. ' .
                'Running <fg=yellow>`php aeros run:database -c -a`</> command to create it...'
            );

            $this->getApplication()->doRun(
                new ArrayInput([
                    'command' => 'run:database',
                    '-c' => true,
                    '-a' => true,
                    '-e' => true

                ]),
                $output
            );
        }

        $migrations = new Process([
            './vendor/bin/phinx',
            'migrate'
        ]);

        $migrations->mustRun();
        $output->write('<fg=green>'. $migrations->getOutput() . '</>');
        $output->writeln('... <fg=green;options=bold>OK.</>');

        // ---------------------------------------------------
        // Cache checking
        $defaultCacheConn = implode(config('cache.default'));

        $output->write(
            sprintf(
                '==> Checking default <fg=yellow>%s</> cache connection status... ',
                $defaultCacheConn
            )
        );

        $cacheStatus = false;

        switch ($defaultCacheConn) {
            case 'memcached':
                $cacheStatus = (cache()->getStats() !== false) ?: false;
                break;

            case 'redis':
                $cacheStatus = (cache()->ping() == 'PONG') ?: false;
                break;
        }

        if (! $cacheStatus) {
            $output->writeln(
                sprintf('<bg=red;options=bold>Error</> Cache connection <fg=yellow>%s</> could not be stablished', $defaultCacheConn)
            );

            return Command::FAILURE;
        }

        $output->writeln('<fg=green;options=bold>OK.</>');

        // ---------------------------------------------------
        // Warm the app up
        $output->writeln(sprintf('==> Warming up the application <fg=bright-green;options=bold>"%s"</>', env('APP_NAME')));

        $this->getApplication()->doRun(
            new ArrayInput([
                'command' => 'run:warmup'
            ]),
            $output
        );

        // ---------------------------------------------------
        // Waking up workers
        $output->writeln('==> Waking up workers... ');

        $this->getApplication()->doRun(
            new ArrayInput([
                'command' => 'run:worker',
                '--all' => true,
            ]),
            $output
        );

        $output->writeln('... <fg=green;options=bold>OK.</>');

        return Command::SUCCESS;
    }
}
