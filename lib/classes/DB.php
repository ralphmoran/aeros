<?php

namespace Classes;

use Interfaces\DBHandlerInterface;

class Db extends Kernel
{
    /**
     * Generic method to handle diffrent DB drivers.
     *
     * @param string $db_driver
     * @return DBHandlerInterface
     */
    public function connect(string $db_driver = ''): DBHandlerInterface
    {
        return match ($db_driver) {
                'mysqli' => \Classes\MySQLDriver::getInstance(),
                default => \Classes\MySQLDriver::getInstance(),
            };
    }
}
