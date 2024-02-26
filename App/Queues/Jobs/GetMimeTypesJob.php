<?php

namespace Aeros\App\Queues\Jobs;

use Aeros\Src\Classes\Job;

class GetMimeTypesJob extends Job
{
    /**
     * This method is called when the job is executed.
     *
     * @return boolean
     */
    public function doWork(): bool
    {
        (new \Aeros\App\Providers\MimeTypeServiceProvider)->boot();

        return true;
    }
}
