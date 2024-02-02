<?php

namespace Aeros\Src\Classes;

class Logger
{
    /**
     * Logs a message into $logFile. If file does not exist, it will be created.
     *
     * @param mixed $msg
     * @param string $logFile Path and filename. If empty, error.log is set.
     * @param bool $createFile Flag to create the log file if it does not exist.
     * @return boolean
     */
    public function log(mixed $msg, string $logFile = '', bool $createFile = false): bool
    {
        $logFile = empty($logFile) ? app()->basedir . '/logs/error.log' : $logFile;

        if (! file_exists($logFile) && ! $createFile) {
            error_log(
                sprintf('[' . date('Y-m-d H:i:s') . '] File: "%s" does not exist.' . PHP_EOL, $logFile), 
                3, 
                app()->basedir . '/logs/error.log'
            );
        }

        return error_log(
            sprintf('[' . date('Y-m-d H:i:s') . '] %s' . PHP_EOL, (! is_string($msg) ? serialize($msg) : $msg)), 
            3, 
            $logFile
        );
    }
}
