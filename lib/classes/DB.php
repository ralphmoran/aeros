<?php

namespace Classes;

class Db
{
    /**
     * Generic method to handle diffrent DB drivers.
     *
     * @param string $driver
     * @return \PDO
     */
    public function connect(string $driver = ''): \PDO
    {
        $dbSetup = config('db');

        $driver = $driver ?: $dbSetup['default'];

        // DB Credentials
        $server = array_key_exists('server', $dbSetup['drivers'][$driver]) 
            ? $dbSetup['drivers'][$driver]['server'] 
            : '';

        $database = array_key_exists('database', $dbSetup['drivers'][$driver]) 
            ? $dbSetup['drivers'][$driver]['database'] 
            : '';

        $username = array_key_exists('username', $dbSetup['drivers'][$driver]) 
            ? $dbSetup['drivers'][$driver]['username'] 
            : '';

        $password = array_key_exists('password', $dbSetup['drivers'][$driver]) 
            ? $dbSetup['drivers'][$driver]['password'] 
            : '';

        $protocol = array_key_exists('protocol', $dbSetup['drivers'][$driver]) 
            ? $dbSetup['drivers'][$driver]['protocol'] 
            : '';

        $port = array_key_exists('port', $dbSetup['drivers'][$driver]) 
            ? $dbSetup['drivers'][$driver]['port'] 
            : '';

        // Data source name
        $dsn = '';

        switch ($driver) {

            case 'postgres':
                $dsn = "pgsql:host=" . $server . ";dbname=" . $database;
                break;

            case 'sqlite':

                return new \PDO("sqlite:" . $server . "/" . $database . ".sql");

                break;

            case 'mysql':
            default:
                $dsn = "mysql:host=" . $server . ";dbname=" . $database . ";charset=UTF8";
        }

        return new \PDO(
            $dsn, 
            $username, 
            $password,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }
}
