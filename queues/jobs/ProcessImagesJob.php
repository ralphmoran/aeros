<?php

namespace Aeros\Queues\Jobs;

use Aeros\Lib\Classes\Job;

class ProcessImagesJob extends Job
{
    public function doWork(): bool
    {
        logger(
            'Doing work for: ' . __CLASS__, 
            app()->basedir . '/logs/jobs.log'
        );

        return true;
    }
}
