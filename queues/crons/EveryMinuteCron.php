<?php

namespace Aeros\Queues\Crons;

use Aeros\Lib\Classes\Cron;

class EveryMinuteCron extends Cron
{
    protected string $id = 'EveryMinute';

    /**
     * This method is called when main scheduler cron is invoked.
     *
     * @return void
     */
    public function run()
    {
        app()
            ->scheduler
            ->raw('ps aux | grep php')
            ->everyMinute()
            ->before(function() {
                logger(
                    'Starting ' . __CLASS__ . ' at ' . microtime(), 
                    app()->basedir . '/logs/cron.log'
                );
            })
            ->then(function ($output) {
                logger(
                    'Finished ' . __CLASS__ . ' at ' . microtime() . ':: ' . print_r($output, true), 
                    app()->basedir . '/logs/cron.log'
                );
            });
    }

    public function work()
    {
        
    }
}
