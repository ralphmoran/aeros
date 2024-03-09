<?php

namespace App\Queues\Crons;

use Aeros\Src\Classes\Cron;

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
                    app()->basedir . '/logs/cron.log'
                );
            })
            ->then(function ($output) {
                logger(
                    'Finished ' . __CLASS__ . ' at ' . microtime() . ':: '. print_r($output, true), 
                    app()->basedir . '/logs/cron.log'
                );
            });
    }

    public function work()
    {
        
    }
}
