<?php

namespace Crons;

use Classes\Cron;

class Every10MinutesCron extends Cron
{
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
                logger('Starting ' . __CLASS__ . ' at ' . microtime(), app()->basedir . '/logs/cron.log');
            })
            ->then(function ($output) {
                logger($output, app()->basedir . '/logs/cron.log');
            });
    }
}
