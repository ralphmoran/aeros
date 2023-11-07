<?php

namespace Classes;

class Config
{
    /** @var array */
    private array $requestedKeys = [];

    /**
     * Returns an item/value from config files.
     * 
     * Format: "'config_filename'.'parent_node'.'child_node'"
     * 
     * Example: 
     * 
     *      "db.mysql.server" 
     * 
     *      Ends up being: "prod001"
     *      From "./config/db.php": 
     *          [
     *              "mysql" => [
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
        // Return value if $from was requested before
        if (in_array($from, $this->requestedKeys)) {
            return $this->requestedKeys[$from];
        }

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

        $configContent = require $configFile;

        // Config content is an object
        if (is_object($configContent)) {
            return $this->storeRequestedKey($from, $configContent, $default);
        }

        // Return entire array from config file
        // if there is no more requests
        if (count($configParts) == 1) {
            return $this->storeRequestedKey($from, $configContent, $default);
        }

        // Remove first element, this could be the config file name
        array_shift($configParts);

        // Walk through all other parts to get the last key value
        foreach ($configParts as $index) {
            $configContent = $configContent[$index];
        }

        return $this->storeRequestedKey($from, $configContent, $default);
    }

    /**
     * Stores requested key in the store.
     *
     * @param mixed $from
     * @param mixed $configContent
     * @param mixed $default
     * @return mixed
     */
    private function storeRequestedKey($from, $configContent, $default): mixed
    {
        $configContent = $configContent ?: $default;

        $this->requestedKeys[$from] = $configContent;

        return $configContent;
    }
}
