<?php

namespace Aeros\Src\Classes;

/**
 * Schedules execution of jobs at specified intervals
 * 
 * - Jobs run synchronously on a schedule.
 * - Timer-based execution rather on-demand job queueing.
 * - One instance runs at a time.
 * - Periodic batch of jobs.
 * 
 * @link https://packagist.org/packages/peppeocchi/php-cron-scheduler
 */
abstract class Cron
{
    /**
     * Unique cron identifier. This is required by the command "run:cron".
     *
     * @var string
     */
    protected string $id;

    /**
     * Registers a new cron instance into app()->scheduler.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Forces a cron to work.
     *
     * @return void
     */
    abstract public function work();

    /**
     * Constructor to validate basic variables on a cron.
     */
    public function __construct()
    {
        $this->checkCronId();
    }

    /**
     * Checks the unique cron identifier.
     *
     * @return void
     * @throws Exception
     */
    public function checkCronId()
    {
        if (empty($this->id)) {
            throw new \Exception('ERROR[Cron] "' . get_called_class() . '" requires an id.');
        }
    }

    /**
     * Returns the cron identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
