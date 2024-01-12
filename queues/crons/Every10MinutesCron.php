<?php

namespace Aeros\Queues\Crons;

use Aeros\Lib\Classes\Cron;

class Every10MinutesCron extends Cron
{
    protected string $id = 'Every10Minutes';

    /**
     * This method is called when main scheduler cron is invoked.
     *
     * @return void
     */
    public function run()
    {
        app()
            ->scheduler
            ->raw('ps aux | grep httpd')
            ->everyMinute(10)
            ->before(function() {
                logger(
                    'Starting ' . __CLASS__ . ' at ' . microtime(), 
                    app()->basedir . '/logs/cron.log',
                    true
                );
            })
            ->then(function ($output) {
                logger(
                    'Finished ' . __CLASS__ . ' at ' . microtime() . serialize($output), 
                    app()->basedir . '/logs/cron.log',
                    true
                );
            });
    }

    public function work()
    {
        
    }
}
