<?php

namespace Jobs;

use Interfaces\JobInterface;

class ProcessImagesJob implements JobInterface
{
    public function doWork()
    {
        app()->logger
            ->log(
                'Doing work for: ' . __CLASS__, 
                app()->basedir . '/logs/jobs.log'
            );
    }
}
