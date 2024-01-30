<?php

namespace Aeros\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CacheClearCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'cache:clear';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros cache:clear
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php cache:clear --help`
        $this->setDescription('Clears or flushes cache per keys or all.')
            ->setHelp('Commands help...');

        $this->addArgument(
            'keys', 
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 
            'Argument "keys" (array). Example: `$ php aeros cache:clear memcached:cache.routes sqlite:cache.middlewares`'
        );

        $this->addOption('flush', 'f', InputOption::VALUE_NONE, 'Option "flush" with alias "f", if provided, it flushes all cache drivers.');
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
        // Flush all cache connections
        if ($input->getOption('flush')) {

            $question = new ConfirmationQuestion(
                'This action is destructive. Do you want to continue? [Y/n] ', 
                false,
                '/^(y|Y)/i'
            );

            $cacheConnections = config('cache.connections');

            if ($this->getHelper('question')->ask($input, $output, $question)) {

                foreach ($cacheConnections as $connection => $setup) {
                    switch ($setup['driver']) {
                        case 'memcached':
                            cache($connection)->flush();
                            break;
                        case 'redis':
                            cache($connection)->flushdb();
                            break;
                    }
                }

                $output->writeln("All cache connections were flushed.");

                return Command::SUCCESS;
            }
        }

        // Example : `php aeros cache:clear redis-conn:cache.routes memcached-conn:cache.routes`
        // Delete all requested "$keys"
        if ($keys = $input->getArgument('keys')) {

            $question = new ConfirmationQuestion(
                "Are you sure you want to delete permanentely these keys? [y/N] ", 
                false,
                '/^(y|Y)/i'
            );

            if ($this->getHelper('question')->ask($input, $output, $question)) {

                $progressBar = new ProgressBar($output, count($keys));
                $progressBar->setFormatDefinition(
                    'custom', 
                    " %current%/%max% [%bar%] %message% %percent:3s%% %elapsed:6s%/%estimated:-6s%\n"
                );
                $progressBar->setFormat('custom');
                $progressBar->setMessage('Start');

                $progressBar->start();

                // Delete each key
                foreach ($keys as $key) {

                    [$connectionName, $cacheKey] = explode(':', $key);

                    if (! cache($connectionName)->exists($cacheKey)) {
                        $progressBar->setMessage('Key: ' . $cacheKey . ' does not exist.');
                        $progressBar->advance();
                        continue;
                    }

                    $progressBar->setMessage('Deleting key: ' . $cacheKey);
                    cache($connectionName)->del($cacheKey);

                    $progressBar->advance();
                }

                // ensures that the progress bar is at 100%
                $progressBar->setMessage('Completed');
                $progressBar->finish();

                return Command::SUCCESS;
            }
        }

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
