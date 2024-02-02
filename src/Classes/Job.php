<?php

namespace Aeros\Src\Classes;

use Ramsey\Uuid\Uuid;

abstract class Job
{
    /** @var string */
    public string $uuid;

    /**
     * Constructor that adds a UUID to each job.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    /**
     * Abstract method that runs the job work.
     *
     * @return bool
     */
    abstract public function doWork(): bool;
}
