<?php

namespace Aeros\Queues\Crons;

use Aeros\Lib\Classes\Cron;

class GetMimeTypesCron extends Cron
{
    protected string $id = 'GetMimeTypes';

    /**
     * This method is called when main scheduler cron is invoked.
     *
     * @return void
     */
    public function run()
    {
        app()
            ->scheduler
            ->call(function() {
                logger(
                    'Updating MIME types', 
                    app()->basedir . '/logs/cron.log'
                );

                (new \Aeros\Providers\MimeTypeServiceProvider)->boot();
            })
            ->sunday()
            ->then(function ($output) {
                logger(
                    'Updated MIME types', 
                    app()->basedir . '/logs/cron.log'
                );
            });
    }

    /**
     * Requests and sets MIME types.
     *
     * @return void
     */
    public function work()
    {
        (new \Aeros\Providers\MimeTypeServiceProvider)->boot();
    }
}
