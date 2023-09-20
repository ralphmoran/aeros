<?php

namespace Classes;

class Logger
{
    /**
     * Logs a message into $logFile. If file does not exist, it will be created.
     *
     * @param string $msg
     * @param string $logFile
     * @return boolean
     */
    public function log(string $msg, string $logFile): bool
    {
        return error_log(
            sprintf('[' . date('Y-m-d H:i:s') . '] %s' . PHP_EOL, $msg), 
            3, 
            $logFile
        );
    }
}
