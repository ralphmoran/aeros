<?php

namespace Classes;

// https://www.php.net/manual/en/class.pdo.php
// https://www.php.net/manual/en/class.pdostatement.php 

class Db
{
    /** @var array */
    private $activeDBConnections = [];

    /** @var string|null */
    private $dbConnectionAlias = null;
    
    /** @var string|null */
    private $driver = null;

    /** @var PDO|null */
    private $reflectionPDO = null;

    /** @var PDOStatement */
    private $stm = null;

    /** @var array */
    private $nulledMethods = [
        'beginTransaction',
        'commit',
    ];
    
    /** @var array */
    private $stmPDOMethods = [
        'execute',
    ];

    /**
     * Generic method to handle diffrent DB drivers.
     *
     * @param ?string $driver `sqlite` or `sqlite:db_alias`
     * @return Db
     */
    public function connect(string $driver = null): Db
    {
        $dbSetup = config('db');

        // Driver comes with an alias
        if (strpos($driver, ':') !== false) {
            $driverParts =  array_filter(explode(':', $driver), function ($alias) {
                    return ! empty($alias) ? $alias : null;
                }
            );

            $this->driver = $driver = $driverParts[0];
            $this->dbConnectionAlias = $driverParts[1];
        }

        $this->driver = $driver ?? $dbSetup['default'][0];

        // Return PDO object by alias, if exists
        if (array_key_exists($this->dbConnectionAlias, $this->activeDBConnections)) {
            return $this->activeDBConnections[$this->dbConnectionAlias];
        }

        // Return PDO object by driver, if exists
        if (array_key_exists($driver, $this->activeDBConnections)) {
            return $this->activeDBConnections[$driver];
        }

        switch ($this->driver) {
            case 'postgres':
                $this->activeDBConnections[$this->dbConnectionAlias ?? $this->driver] = $this->gePostgresPDO();
                break;
            case 'sqlite':
                $this->activeDBConnections[$this->dbConnectionAlias ?? $this->driver] = $this->getSqlitePDO();
                break;
            case 'mysql':
                $this->activeDBConnections[$this->dbConnectionAlias ?? $this->driver] = $this->getMysqlPDO();
                break;
            default:
                throw new \PDOException(
                    sprintf(
                        'ERROR[PDOException] DB Driver "%s:%s" not found or invalid.', 
                        $this->driver,
                        $this->dbConnectionAlias
                    )
                );
        }

        return $this;
    }

    /**
     * Makes mathod calls to PDO connection.
     *
     * @param string $method
     * @param array $arguments
     * @return void
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments)
    {

        // https://www.php.net/manual/en/pdo.prepare.php

        // There are some PDO methods that are not supported due to the global try-catch
        if (in_array($method, $this->nulledMethods)) {
            return $this;
        }

        if (is_null($this->reflectionPDO)) {
            $this->reflectionPDO = new \ReflectionClass('PDO');
        }

        // Validates if method exists
        if ($this->reflectionPDO->hasMethod($method)) {

            $reflectionMethod = $this->reflectionPDO->getMethod($method);

            // Call it when there are no parameters
            if (! $reflectionMethod->getNumberOfParameters()) {

                $this->stm = $reflectionMethod->invoke(
                    $this->activeDBConnections[$this->dbConnectionAlias ?? $this->driver]
                );

                if ($this->stm instanceof \PDOStatement) {
                    return $this->stm;
                }

                return $this;
            }

            // Call it with parameters
            $this->stm = $reflectionMethod->invokeArgs(
                $this->activeDBConnections[$this->dbConnectionAlias ?? $this->driver], 
                $arguments
            );

            if ($this->stm instanceof \PDOStatement) {
                return $this->stm;
            }

            return $this;
        }

        throw new \BadMethodCallException(
            sprintf(
                'ERROR[BadMethodCallException] Method "%s" does not exist.', 
                $method
            )
        );
    }

    /**
     * Connects to PostgreSQL database.
     *
     * @return \PDO
     */
    private function gePostgresPDO(): \PDO
    {
        $dbSetup = config('db.drivers.postgres');

        return new \PDO(
            "pgsql:host=" . $dbSetup['server'] . ";dbname=" . $dbSetup['database'], 
            $dbSetup['username'], 
            $dbSetup['password'], 
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * Connects to SQLite database.
     *
     * @return void
     */
    private function getSqlitePDO(): \PDO
    {
        $dbSetup = config('db.drivers.sqlite');

        if (strtolower(pathinfo($dbSetup['database'])['extension']) !== 'sql') {
            throw new \InvalidArgumentException(
                sprintf('ERROR[InvalidArgumentException] Database file name "%s" is not valid.', $dbSetup['database'])
            );
        }

        return new \PDO("sqlite:" . $dbSetup['server'] . "/" . $dbSetup['database'], null, null, [
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 1,
        ]);
    }

    /**
     * Connects to MySQL database.
     *
     * @return \PDO
     */
    private function getMysqlPDO(): \PDO
    {
        $dbSetup = config('db.drivers.mysql');

        return new \PDO(
            "mysql:host=" . $dbSetup['server'] . ";dbname=" . $dbSetup['database'] . ';charset=UTF8', 
            $dbSetup['username'], 
            $dbSetup['password'], 
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }
}
