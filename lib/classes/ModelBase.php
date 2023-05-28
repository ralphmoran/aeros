<?php

namespace Classes;

use Interfaces\DBHandlerInterface;

class ModelBase extends Singleton
{
    /** @var boolean */
    protected $succeeded = false;

    /**
     * Generic method to handle diffrent DB drivers.
     *
     * @param string $db_driver
     * @return DBHandlerInterface
     */
    protected function db(string $db_driver = '') : DBHandlerInterface
    {
        return match ($db_driver) {
                'mysqli' => \Classes\MySQLDriver::getInstance(),
                default => \Classes\MySQLDriver::getInstance(),
            };
    }
}
