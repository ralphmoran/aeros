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
        $this->setDescription('Aeros REPL - "cache:clear" command.')
            ->setHelp('Commands help...');
        
        // Adding arguments
        // InputArgument::REQUIRED
        // InputArgument::OPTIONAL
        // InputArgument::IS_ARRAY
        $this->addArgument(
            'keys', 
            InputArgument::IS_ARRAY, 
            'Argument "keys" (array). Example: `$ php aeros clear:cache cache.routes cache.middlewares`'
        );

        // Adding options
        // InputOption::VALUE_NONE = 1; // Do not accept input for the option (e.g. --yell).
        // InputOption::VALUE_REQUIRED = 2; // e.g. --iterations=5 or -i5
        // InputOption::VALUE_OPTIONAL = 4; // e.g. --yell or --yell=loud
        // InputOption::VALUE_IS_ARRAY = 8; // The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
        // InputOption::VALUE_NEGATABLE = 16; // The option may have either positive or negative value (e.g. --ansi or --no-ansi).
        $this->addOption('flush', 'f', InputOption::VALUE_NONE, 'Option "flush" with alias "f"');
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
        // Flush all cache: delete all Redis keys
        if ($input->getOption('flush')) {

            $question = new ConfirmationQuestion(
                'Continue with this action? [y/N] ', 
                false,
                '/^(y|Y)/i'
            );

            if ($this->getHelper('question')->ask($input, $output, $question)) {

                $keys = cache()->keys('*');

                $output->writeln(sprintf("Keys to be eliminate: \n\n%s", implode("\n", $keys)));

                # TODO: Process all Redis keys. Use a ProgressIndicator instead
                $progressBar = new ProgressBar($output, count($keys));
                $progressBar->start();

                // Delete each key
                foreach ($keys as $key) {
                    # TODO: Delete each key
                    $progressBar->advance();
                }

                // ensures that the progress bar is at 100%
                $progressBar->finish();

                cache()->flushdb();

                $output->writeln("\n\nDone. \n");

                return Command::SUCCESS;
            }
        }

        // Delete all requested "$keys"
        if ($keys = $input->getArgument('keys')) {

            $question = new ConfirmationQuestion(
                "Are you sure you want to delete permanentely these keys? [y/N]\nKeys:  " . implode(', ', $keys), 
                false,
                '/^(y|Y)/i'
            );

            if ($this->getHelper('question')->ask($input, $output, $question)) {

                $progressBar = new ProgressBar($output, count($keys));
                $progressBar->start();

                // Delete each key
                foreach ($keys as $key) {
                    cache()->del($key);
                    $progressBar->advance();
                }

                // ensures that the progress bar is at 100%
                $progressBar->finish();

                return Command::SUCCESS;
            }
        }

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
}
