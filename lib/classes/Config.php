<?php

namespace Classes;

class Config
{
    /**
     * Returns an item/value from config files.
     * 
     * Format: "'config_filename'.'parent_node'.'child_node'"
     * 
     * Example: 
     * 
     *      "db.mysqli.server" 
     * 
     *      Ends up being: "prod001"
     *      From "./config/db.php": 
     *          [
     *              "mysqli" => [
     *                 "server" => "prod001" // <== Returned value: "prod001"
     *              ]
     *          ]
     *
     * @param string $from Format: "'config_filename'.'parent_node'.'child_node'"
     * @param mixed $default
     * @return mixed
     */
    public function getFrom(string $from, mixed $default = null): mixed
    {
        // Explode string to get config parts
        $configParts = array_filter(
            explode('.', $from),
            function ($item) {
                return trim($item, '.');
            }
        );

        // Config file: app.php, db.php, providers.php, etc
        if (! isset($configParts[0]) 
            || ! file_exists($configFile = app()->basedir . '/config/' . $configParts[0] . '.php')) {
            return $default;
        }

        $configContent = include($configFile);

        // Return entire array from config file
        // if there is no more requests
        if (count($configParts) == 1) {
            return $configContent ?: $default;
        }

        array_shift($configParts);

        foreach ($configParts as $index) {
            $configContent = $configContent[$index];
        }

        return $configContent ?: $default;
    }
}
