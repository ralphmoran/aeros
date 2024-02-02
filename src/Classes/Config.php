<?php

namespace Aeros\Src\Classes;

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
     *      config("db.mysql.server")
     * 
     *      Ends up being: "prod001"
     *      From "./config/db.php": 
     *          [
     *              "mysql" => [
     *                 "server" => "prod001" // <== Returned value: "prod001"
     *              ]
     *          ]
     *
     * @param string $from Format: config_filename[.parent_node[.child_node[. ...]]]
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

        // Return entire array from config file
        // Config content is an object or there is no more requests
        if (is_object($configContent) || count($configParts) == 1) {
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
     * @param string $from
     * @param mixed $configContent
     * @param mixed $default
     * @return mixed
     */
    private function storeRequestedKey(string $from, $configContent, $default): mixed
    {
        $configContent = $configContent ?: $default;

        $this->requestedKeys[$from] = $configContent;

        return $configContent;
    }
}
