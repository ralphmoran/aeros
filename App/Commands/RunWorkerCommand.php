<?php

namespace Aeros\App\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RunWorkerCommand extends Command
{
    /** @var string Command name */
    protected static $defaultName = 'run:worker';

    /**
     * Sets descriptions, options or arguments.
     * 
     * ```php
     * $ php aeros run:worker
     * ```
     * @link https://symfony.com/doc/current/components/console.html
     * @return void
     */
    protected function configure()
    {
        // Adding command description. 
        // This text will be displayed when: `$ php run:worker --help`
        $this->setDescription('Aeros REPL - "run:worker" command.')
            ->setHelp('Commands help...');
        
        // Adding arguments
        $this->addArgument(
            'workers', 
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 
            'Argument workers (space separated)'
        );

        // Adding options
        $this->addOption(
            'all', 
            'a', 
            InputOption::VALUE_NONE, 
            'Option "all" with alias "a", if provided, it wakes up all registered workers'
        );
        
        $this->addOption(
            'stop', 
            'x', 
            InputOption::VALUE_NONE, 
            'Option "stop" with alias "x", if provided, it stops all registered workers'
        );
        
        $this->addOption(
            'wake', 
            'w', 
            InputOption::VALUE_NONE, 
            'Option "wake" with alias "w", if provided, it wakes up the requested worker(s)'
        );
        
        $this->addOption(
            'status', 
            's', 
            InputOption::VALUE_NONE, 
            'Option "status" with alias "s", if provided, it returns status of workers or the requested names'
        );

        $this->addOption(
            'update', 
            'u', 
            InputOption::VALUE_NONE, 
            'Option "update" with alias "u", if provided, it udpates all worker groups or a specific, if any'
        );

        $this->addOption(
            'read', 
            'r', 
            InputOption::VALUE_NONE, 
            'Option "read" with alias "r", if provided, it reads all worker groups or a specific, if any'
        );

        $this->addOption(
            'refresh', 
            'f', 
            InputOption::VALUE_NONE, 
            'Option "refresh" with alias "f", if provided, it stop, remove, update, and reread all workers'
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
        // All workers
        if ($input->getOption('all')) {

            // Status
            if ($input->getOption('status')) {
                $output->writeln($this->processWorkerAction('status'));

                return Command::SUCCESS;
            }

            // Read
            if ($input->getOption('read')) {
                $output->writeln($this->processWorkerAction('reread', ''));

                return Command::SUCCESS;
            }
            
            // Update
            if ($input->getOption('update')) {

                $workerProcess = new Process(array_merge(
                    ['/usr/bin/sudo', '/usr/bin/cp', '-r'], 
                    glob('/var/www/html/app/queues/workers/scripts/supervisor/*.conf'),
                    ['/etc/supervisor/conf.d/']
                ));

                $workerProcess->mustRun();

                $output->writeln($workerProcess->getOutput());
                $output->writeln($this->processWorkerAction('update'));
                $output->writeln($this->processWorkerAction('reread', ''));

                return Command::SUCCESS;
            }

            // Stop workers
            if ($input->getOption('stop')) {
                $output->writeln($this->processWorkerAction('stop'));

                return Command::SUCCESS;
            }

            // Refresh workers
            if ($input->getOption('refresh')) {

                $question = new ConfirmationQuestion(
                    "This action will stop, remove, move, reread, and restart workers. Do you want to continue? [y/N] ", 
                    false,
                    '/^(y|Y)/i'
                );

                if ($this->getHelper('question')->ask($input, $output, $question)) {

                    $output->writeln(trim($this->processWorkerAction('stop')));

                    // Remove
                    if (! empty($currentWorkers = glob('/etc/supervisor/conf.d/*'))) {
                        $removeWorkers = new Process(array_merge(
                            ['/usr/bin/sudo', '/usr/bin/rm'], 
                            $currentWorkers
                        ));

                        $removeWorkers->mustRun();
                    }

                    // Move workers
                    if (! empty($newWorkers = glob('/var/www/html/app/queues/workers/scripts/supervisor/*.conf'))) {
                        $workerProcess = new Process(array_merge(
                            ['/usr/bin/sudo', '/usr/bin/cp', '-r'], 
                            $newWorkers,
                            ['/etc/supervisor/conf.d/']
                        ));

                        $workerProcess->mustRun();
                    }

                    if (empty($newWorkers)) {
                        $output->writeln('<fg=yellow;options=bold>No new worker files were found</>');
                    }

                    $output->writeln(trim($this->processWorkerAction('update')));
                    $output->writeln(trim($this->processWorkerAction('reread', '')));
                    $output->writeln(trim($this->processWorkerAction()));
                }

                return Command::SUCCESS;
            }

            // Start workers
            $output->writeln($this->processWorkerAction());

            return Command::SUCCESS;
        }

        // Only for specific workers
        if (! $input->getOption('all')) {

            // Status
            if ($input->getOption('status') && $workers = $input->getArgument('workers')) {
                $output->writeln($this->processWorkerAction('status', $workers));

                return Command::SUCCESS;
            }

            // Update
            // if ($input->getOption('update') && $workers = $input->getArgument('workers')) {
            //     $output->writeln($this->processWorkerAction('update', $workers));

            //     return Command::SUCCESS;
            // }

            // Stop
            if ($input->getOption('stop') && $workers = $input->getArgument('workers')) {
                $output->writeln($this->processWorkerAction('stop', $workers));

                return Command::SUCCESS;
            }

            // Wake up
            if ($input->getOption('wake') && $workers = $input->getArgument('workers')) {
                $output->writeln(
                    $this->processWorkerAction('start', $workers)
                );
            }
        }

        return Command::INVALID;
    }

    /**
     * Process worker action.
     *
     * @param   string          $action     The action to perform on the worker(s). 
     *                                      Default is 'start'.
     * @param   string|array    $workerName The name(s) of the worker(s) to perform 
     *                                      the action on. Default is '*'.
     *                                      If '*' is provided, the action will 
     *                                      be applied to all workers.
     * @return  string          Returns the concatenated output of all worker 
     *                          processes.
     */
    private function processWorkerAction(string $action = 'start', string|array $workerName = '*'): string
    {
        if ($action == 'start') {

            // Reread
            $reread = new Process([
                '/usr/bin/sudo', 
                '/usr/bin/supervisorctl', 
                'reread'
            ]);

            $reread->mustRun();

            // Update
            $update = new Process([
                '/usr/bin/sudo', 
                '/usr/bin/supervisorctl', 
                'update'
            ]);

            $update->mustRun();
        }

        $status = [];

        $workerName = ($workerName == '*') ? 'all' : $workerName;
        $workers = is_string($workerName) ? [$workerName] : $workerName;

        foreach ($workers as $worker) {
            $workerProcess = new Process([
                '/usr/bin/sudo', 
                '/usr/bin/supervisorctl', 
                $action,
                $worker
            ]);
    
            $workerProcess->mustRun();
    
            $status[] = $workerProcess->getOutput();
        }

        return implode($status);
    }
}
