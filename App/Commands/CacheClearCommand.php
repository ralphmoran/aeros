<?php

namespace Aeros\App\Commands;

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
        $this->setDescription('Clears or flushes cache per keys or all cache connections.');

        $this->addArgument(
            'keys', 
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 
            'Argument "keys" (array). Example: `$ php aeros cache:clear memcached:cache.routes sqlite:cache.middlewares`'
        );

        $this->addOption(
            'flush', 
            'f', 
            InputOption::VALUE_NONE, 'Option "flush" with alias "f", if provided, it flushes all cache drivers.'
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
        // Flush all cache connections
        if ($input->getOption('flush')) {

            $question = new ConfirmationQuestion(
                'This action is destructive. Do you want to continue? [Y/n] ', 
                false,
                '/^(y|Y)/i'
            );

            if ($this->getHelper('question')->ask($input, $output, $question)) {

                $cacheConnections = config('cache.connections');

                foreach ($cacheConnections as $connection => $setup) {
                    $this->flushCacheConnection($connection);
                }

                $output->writeln("All cache connections were flushed.");

                return Command::SUCCESS;
            }
        }

        // Example : `php aeros cache:clear redis-conn:* memcached-conn:cache.routes`
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

                $cacheConnections = config('cache.connections');

                // Delete each key
                foreach ($keys as $key) {

                    [$connectionName, $cacheKey] = explode(':', $key);

                    // Delete all keys from $connectionName (flush)
                    if ($cacheKey == '*') {
                        if ($this->flushCacheConnection($connectionName)) {
                            $progressBar->setMessage('Flushing connection name: ' . $connectionName);
                            $progressBar->advance();

                            continue;
                        }
                    }

                    // Check the cache driver in order to use the correct method to delete the key
                    if (in_array($connectionName, array_keys($cacheConnections))) {

                        $setup = $cacheConnections[$connectionName];

                        switch ($setup['driver']) {
                            case 'memcached':
                                cache($connectionName)->get($cacheKey);

                                if (cache($connectionName)->getResultCode() != \Memcached::RES_NOTFOUND) {
                                    cache($connectionName)->delete($cacheKey);
                                    $progressBar->setMessage('Deleting key: ' . $cacheKey);
                                    $progressBar->advance();

                                    break;
                                }

                                logger(sprintf('Key "%s" was not found in "%s" cache connection', $cacheKey, $connectionName));

                                break;
                            case 'redis':
                                if (cache($connectionName)->exists($cacheKey)) {
                                    cache($connectionName)->del($cacheKey);
                                    $progressBar->setMessage('Deleting key: ' . $cacheKey);
                                    $progressBar->advance();

                                    break;
                                }

                                logger(sprintf('Key "%s" was not found in "%s" cache connection', $cacheKey, $connectionName));

                                break;
                            default:
                                throw new \Exception(
                                    sprintf(
                                        'ERROR[Cache driver] Cache driver "%s" is not supported.', 
                                        $setup['driver']
                                    )
                                );
                        }
                    }

                    // Connection name does not exist
                    if (! in_array($connectionName, array_keys($cacheConnections))) {
                        throw new \Exception(
                            sprintf(
                                'ERROR[Cache connection] Cache connection "%s" not found.', 
                                $connectionName
                            )
                        );
                    }
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

    /**
     * Flushes a connection based on a name.
     *
     * @param string $connectionName
     * @return boolean
     * @throws \Exception
     */
    private function flushCacheConnection(string $connectionName): bool
    {
        $cacheConnections = config('cache.connections');

        if (in_array($connectionName, array_keys($cacheConnections))) {

            $setup = $cacheConnections[$connectionName];

            switch ($setup['driver']) {
                case 'memcached':
                    cache($connectionName)->flush();
                    break;
                case 'redis':
                    cache($connectionName)->flushdb();
                    break;
            }

            return true;
        }

        throw new \Exception(
            sprintf(
                'ERROR[Cache connection] Cache connection "%s" not found.', 
                $connectionName
            )
        );
    }
}
