<?php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeWorkerCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'make:worker';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros make:worker
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:worker --help`
        $this->setDescription('Aeros REPL - "make:worker" command.');
        
        // Adding arguments
        $this->addArgument('name', InputArgument::REQUIRED, 'Argument "name" (required)');

        // Adding options
        $this->addOption('processes', 'p', InputOption::VALUE_OPTIONAL, 'Option "processes" with alias "p"');
    }

    /**
     * Creates a new worker class, worker log file, and a worker conf file.
     *
     * @param string $name Format: 'example-worker' => 'ExampleWorker' for worker class, 
     *                                   'example-worker' => 'example-worker-script' for worker script
     *                                   'example-worker' => 'example-worker-script' for worker conf file
     * @param integer $proccesses
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! empty($name = $input->getArgument('name'))) {

            // Give the proper format
            $workerName = implode('',
                array_map(
                    'ucfirst', 
                    explode('-', $name)
                )
            );

            // Create worker class
            app()->file->createFromTemplate(
                $workerClass = env('WORKERS_DIR') . '/' . $workerName . '.php', 
                app()->basedir . '/templates/TemplateWorker.template', 
                ['worker-name' => $workerName]
            );

            // Create worker script file
            app()->file->createFromTemplate(
                $workerScript = env('SCRIPTS_DIR') . '/' . $name . '.php', 
                app()->basedir . '/templates/template-worker-script.template', 
                ['worker-name' => $workerName,]
            );

            $processes = $input->getOption('processes') ?: 3;

            // Create config worker file
            app()->file->createFromTemplate(
                $workerConf = env('WORKERS_CONF_DIR') . '/' . $name . '.conf', 
                app()->basedir . '/templates/conf.template', 
                [
                    'script-name' => $name,
                    'process-num' => $processes,
                ]
            );

            // Create log file for new worker
            app()->file->create($workerLog = env('LOGS_DIR') . '/' . $name . '.log');

            $output->writeln([
                sprintf('<info>4 new files were created for worker: %s</info>', $workerName),
                $workerClass,
                $workerScript,
                $workerConf,
                $workerLog
            ]);

            return Command::SUCCESS;
        }

        $output->writeln("Worker name is required");

        return Command::FAILURE;
    }
}
