<?php

namespace Jobs;

use Classes\Job;

class SendEmailsJob extends Job
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
