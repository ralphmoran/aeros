<?php

namespace Aeros\Src\Commands;

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
     * $ php aeros make:worker AnotherWorker
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php make:worker --help`
        $this->setDescription('Creates a worker class and option a script file, log file, and supervisor config file.');
        
        // Adding arguments
        $this->addArgument('name', InputArgument::REQUIRED, 'Argument "name" (required)');

        // Adding options
        $this->addOption('processes', 'p', InputOption::VALUE_OPTIONAL, 'Option "processes" with alias "p". Default 3.');
        $this->addOption('log', 'l', InputOption::VALUE_NONE, 'Option "log" with alias "l". If provided, it creates a log file.');
        $this->addOption('config', 'c', InputOption::VALUE_NONE, 'Option "config" with alias "c". If provided, it creates a config file.');
        $this->addOption('script', 's', InputOption::VALUE_NONE, 'Option "script" with alias "s". If provided, it creates a script file.');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Option "all" with alias "a". If provided, it creates all files.');
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

            $name = preg_replace('/worker$/i', '', $name);

            // Give the proper format
            $hyphenatedWorkerName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));

            // Create worker class
            app()->file->createFromTemplate(
                $workerClass = env('WORKERS_DIR') . '/' . $name . 'Worker.php', 
                app()->basedir . '/../vendor/aeros/framework/src/resources/templates/worker.template', 
                ['classname' => $name]
            );

            // Create worker script file
            if ($input->getOption('script') || $input->getOption('all')) {
                app()->file->createFromTemplate(
                    $workerScript = env('SCRIPTS_DIR') . '/' . $hyphenatedWorkerName . '-worker-script.php', 
                    app()->basedir . '/../vendor/aeros/framework/src/resources/templates/script.template', 
                    [
                        'worker-name' => $hyphenatedWorkerName,
                        'classname' => $name
                    ]
                );
            }

            // Create config worker file
            if ($input->getOption('config') || $input->getOption('all')) {
                $processes = $input->getOption('processes') ?: 3;

                app()->file->createFromTemplate(
                    $workerConf = env('WORKERS_CONF_DIR') . '/' . $hyphenatedWorkerName . '-worker-script.conf', 
                    app()->basedir . '/../vendor/aeros/framework/src/resources/templates/conf.template', 
                    [
                        'script-name' => $hyphenatedWorkerName . '-worker-script',
                        'process-num' => $processes,
                    ]
                );
            }

            // Create log file for new worker
            if ($input->getOption('log') || $input->getOption('all')) {
                app()->file->create($workerLog = env('LOGS_DIR') . '/' . $hyphenatedWorkerName . '-worker-script.log');
            }

            $output->writeln([
                sprintf('<info>New files were created for worker: %s</info>', $name),
                isset($workerClass) ? $workerClass : '',
                isset($workerScript) ? $workerScript : '',
                isset($workerConf) ? $workerConf : '',
                isset($workerLog) ? $workerLog : ''
            ]);

            return Command::SUCCESS;
        }

        $output->writeln("Worker name is required");

        return Command::FAILURE;
    }
}
