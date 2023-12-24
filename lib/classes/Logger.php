<?php

namespace Classes;

class Logger
{
    /**
     * Logs a message into $logFile. If file does not exist, it will be created.
     *
     * @param mixed $msg
     * @param string $logFile
     * @return boolean
     */
    public function log(mixed $msg, string $logFile): bool
    {
        if (! file_exists($logFile)) {
            throw new \Exception("ERROR[File] Log file '{$logFile}' does not exist.");
        }

        return error_log(
            sprintf('[' . date('Y-m-d H:i:s') . '] %s' . PHP_EOL, (! is_string($msg) ? serialize($msg) : $msg)), 
            3, 
            $logFile
        );
    }
}
