<?php

namespace Aeros\Queues\Jobs;

use Aeros\Lib\Classes\Job;

class GetMimeTypesJob extends Job
{
    /**
     * This method is called when the job is executed.
     *
     * @return boolean
     */
    public function doWork(): bool
    {
        (new \Aeros\Providers\MimeTypeServiceProvider)->boot();

        return true;
    }
}
