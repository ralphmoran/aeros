<?php

namespace Classes;

// https://phpdelusions.net/pdo
// https://www.php.net/manual/en/class.pdo.php
// https://www.php.net/manual/en/class.pdostatement.php 
// https://stackoverflow.com/questions/27902831/sqlite3-sqlstatehy000-general-error-5-database-is-locked
// https://www.sqlite.org/lockingv3.html#how_to_corrupt

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
    
    /** @var PDOStatement|null */
    private $reflectionPDOStatement = null;

    /** @var PDOStatement|null */
    private $stm = null;

    /** @var array */
    private $nulledMethods = [
        'beginTransaction',
        'commit',
    ];

    /** @var array */
    private $connFlags = [
        \PDO::ATTR_PERSISTENT => true,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_TIMEOUT => 1,
    ];

    /**
     * Generic method to handle diffrent DB drivers.
     *
     * @param ?string $driver `sqlite` or `sqlite:db_alias`, if it's null, the driver
     *                          will be get from config('db.default') field.
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
            $this->connFlags
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

        return new \PDO(
            "sqlite:" . $dbSetup['server'] . "/" . $dbSetup['database'], 
            null, 
            null, 
            $this->connFlags
        );
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
            $this->connFlags
        );
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
        // There are some PDO methods that are not supported due to the global try-catch
        if (in_array($method, $this->nulledMethods)) {
            return $this;
        }

        // PDO result
        if (is_null($this->reflectionPDO)) {
            $this->reflectionPDO = new \ReflectionClass('PDO');
        }

        $result = $this->processReflection(
            $this->reflectionPDO, 
            $this->activeDBConnections[$this->dbConnectionAlias ?? $this->driver], 
            $method, 
            $arguments
        );

        if (! is_null($result)) {
            if ($result instanceof \PDOStatement) {
                $this->stm = $result;
            }

            return $this;
        }

        // PDOStatement result
        if (is_null($this->reflectionPDOStatement)) {
            $this->reflectionPDOStatement = new \ReflectionClass('PDOStatement');
        }

        if (! is_null($result = $this->processReflection($this->reflectionPDOStatement, $this->stm, $method, $arguments))) {
            if ($result instanceof \PDOStatement) {
                $this->stm = $result;
            }

            return $this->stm;
        }

        throw new \BadMethodCallException(
            sprintf(
                'ERROR[BadMethodCallException] Method "%s" does not exist.', 
                $method
            )
        );
    }

    /**
     * Processes a reflection object and executes the requested method.
     *
     * @param object $reflection
     * @param object $object
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    private function processReflection($reflection, $object, $method, $arguments): mixed
    {
        if ($reflection->hasMethod($method)) {

            $reflectionMethod = $reflection->getMethod($method);

            // Call it when there are no parameters
            if (! $reflectionMethod->getNumberOfParameters()) {
                $result = $reflectionMethod->invoke($object);
            }

            // Call it with parameters
            if ($reflectionMethod->getNumberOfParameters()) {
                $result = $reflectionMethod->invokeArgs($object, $arguments);
            }

            return $result;
        }

        return null;
    }
}
