<?php

namespace Classes;

use Interfaces\DBHandlerInterface;

class DB
{
    public function __construct(){ }

    public static function raw(string $sql)
    {
        return (new self)->db()->query($sql);
    }

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
