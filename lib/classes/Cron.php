<?php

namespace Classes;

/**
 * Schedules execution of jobs at specified intervals
 * 
 * - Jobs run synchronously on a schedule.
 * - Timer-based execution rather on-demand job queueing.
 * - One instance runs at a time.
 * - Periodic batch of jobs.
 * 
 * - https://packagist.org/packages/peppeocchi/php-cron-scheduler
 */

abstract class Cron
{
    abstract public function run();
}
