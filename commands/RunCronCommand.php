<?php

namespace Commands;

use Classes\Cron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCronCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'run:cron';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros run:cron <CronID> --all|-a
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php run:cron --help`
        $this->setDescription('It makes possible to run scheduled scripts, like warmups, DB clean ups, etc.');
        
        // Adding arguments
        // InputArgument::REQUIRED
        // InputArgument::OPTIONAL
        // InputArgument::IS_ARRAY
        $this->addArgument('name', InputArgument::OPTIONAL, 'Runs the "name" script.');

        // Adding options
        // InputOption::VALUE_NONE = 1; // Do not accept input for the option (e.g. --yell).
        // InputOption::VALUE_REQUIRED = 2; // e.g. --iterations=5 or -i5
        // InputOption::VALUE_OPTIONAL = 4; // e.g. --yell or --yell=loud
        // InputOption::VALUE_IS_ARRAY = 8; // The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
        // InputOption::VALUE_NEGATABLE = 16; // The option may have either positive or negative value (e.g. --ansi or --no-ansi).
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Runs all scheduled scripts.');
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
        $all = $input->getOption('all');
        $name = $input->getArgument('name');

        if ($all || $name) {

            if ($all) {
                $output->writeln("<info>Running all registered crons</info>");
            }

            $path = app()->basedir . '/queues/crons';

            foreach (scan($path) as $cron) {
                require $path . '/' . $cron;

                $cron = '\Crons\\' . rtrim($cron, '.php');

                if (($cronInstance = new $cron()) instanceof Cron) {

                    if ($all || ($name && $cronInstance->getId() == $name)) {

                        $output->writeln(sprintf('Running cron: %s...', $cronInstance->getId()));
                        $cronInstance->work();
                        $output->writeln(sprintf('<info>Finished cron: %s.</info>', $cronInstance->getId()));

                        unset($cronInstance);
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
