<?php

namespace Crons;

use Classes\Cron;

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
                (new \Providers\MimeTypeServiceProvider)->boot();
            })
            ->sunday()
            ->then(function ($output) {
                logger('MIME types updated', app()->basedir . '/logs/cron.log');
            });
    }

    /**
     * Requests and sets MIME types.
     *
     * @return void
     */
    public function work()
    {
        // (new \Providers\MimeTypeServiceProvider)->boot();
    }
}
