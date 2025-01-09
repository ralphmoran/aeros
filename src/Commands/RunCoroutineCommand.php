<?php

namespace Aeros\Src\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use Swoole\Coroutine;
use Swoole\Runtime;

class RunCoroutineCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'run:coroutine';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros run:coroutine
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php run:coroutine --help`
        $this->setDescription('Aeros REPL - "run:coroutine" command.')
            ->setHelp('Commands help...');

        // Adding arguments
        // InputArgument::REQUIRED
        // InputArgument::OPTIONAL
        // InputArgument::IS_ARRAY
        // $this->addArgument('workers', InputArgument::OPTIONAL, 'Number of workers');

        // Adding options
        // InputOption::VALUE_NONE = 1; // Do not accept input for the option (e.g. --yell).
        // InputOption::VALUE_REQUIRED = 2; // e.g. --iterations=5 or -i5
        // InputOption::VALUE_OPTIONAL = 4; // e.g. --yell or --yell=loud
        // InputOption::VALUE_IS_ARRAY = 8; // The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
        // InputOption::VALUE_NEGATABLE = 16; // The option may have either positive or negative value (e.g. --ansi or --no-ansi).
        $this->addOption('stop', 's', InputOption::VALUE_NONE, 'Option "stop" with alias "s"');
        $this->addOption('workers', 'w', InputOption::VALUE_OPTIONAL, 'Option "workers" with alias "w"');
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
        // Set minimum worker number
        $workers = $input->getOption('workers') ?? 4;

        // It stops worker's pool
        if ($input->getOption('stop')) {

            $this->stopWorkerPool($output);

            return Command::SUCCESS;
        }

        // Starting
        $this->startWorkerPool($workers, $output);

        // Success if it's the case. 
        // Other statuses: Command::FAILURE and Command::INVALID
        return Command::SUCCESS;
    }
    
    /**
     * Stops the current active worker pool
     *
     * @param OutputInterface $output
     * @return void
     */
    protected function stopWorkerPool(OutputInterface $output)
    {
        $pids = array_map(function ($pid) {
                return explode(':', $pid)[1] ?? null;
            },
            $key_pids = cache('redis')->keys('worker_pool_pid:*')
        );

        if (empty($pids)) {
            $output->writeln("<fg=yellow>INFO</> No worker pool found to stop.");

            return Command::SUCCESS;
        }

        $output->writeln("<fg=yellow>INFO</> Stopping pool manager PID(s): " . implode(', ', $pids));

        // Kill the worker pool PID(s)
        $migrations = new Process(
            array_merge(
                ['/usr/bin/kill', '-15'], 
                $pids
            )
        );

        $migrations->mustRun();

        // Remove the worker pool PID(s)
        cache('redis')->pipeline(function ($pipe) use ($key_pids){
            foreach ($key_pids as $pid) {
                $pipe->del($pid);
            }
        });

        $output->writeln("<fg=green;options=bold>OK</> Stopped pool manager PID(s): " . implode(', ', $pids));

        return Command::SUCCESS;
    }

    /**
     * Starts a worker pool
     *
     * @param integer $workers
     * @param OutputInterface $output
     * @return void
     */
    protected function startWorkerPool(int $workers, OutputInterface $output)
    {
        $output->writeln("<fg=yellow>INFO</> Starting {$workers} workers...");

        $s = microtime(true);

        Runtime::enableCoroutine();
        Runtime::setHookFlags(Runtime::HOOK_ALL);

        // Create a process pool with workers
        $pool = new \OpenSwoole\Process\Pool($workers);

        // Create a manager process
        $manager = new \OpenSwoole\Process(function($process) use ($pool, $output) {

            $pool->on("WorkerStart", function ($pool, $workerId) use ($output) {
                Coroutine::run(function() use ($workerId) {

                    queue()->processPipeline();

                    // Randomly putting workers asleep to improve performance and resource consumption
                    Coroutine::sleep(random_int(1, 10) / 10);
                });
            });

            $pool->on("WorkerStop", function ($pool, $workerId) {
                // logger("Worker#{$workerId} is stopped", app()->basedir . '/logs/coroutine.log');
            });

            $pool->set([
                'max_request' => 1000,
                'max_wait_time' => 1,
            ]);

            $pool->start();
        }, false, false);

        // Start the manager process
        $manager->start();
        $managerPID = $manager->pid;

        // Save the Worker's pool PID
        Coroutine::run(function() use ($managerPID) {
            cache('redis')->set("worker_pool_pid:{$managerPID}", time());
        });
        
        $output->writeln('<fg=yellow>INFO</> ' . $workers . ' workers started in ' . (microtime(true) - $s) . ' seconds');
        $output->writeln("<fg=yellow>INFO</> To stop the worker pool run ({$managerPID}): `php aeros run:coroutine -s | --stop`");
        $output->writeln("<fg=yellow>INFO</> To restart the worker pool run: `php aeros run:coroutine -r | --restart`");
    }
}
