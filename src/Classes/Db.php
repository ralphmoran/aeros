<?php

namespace Aeros\Src\Classes;

// https://phpdelusions.net/pdo
// https://www.php.net/manual/en/class.pdo.php
// https://www.php.net/manual/en/class.pdostatement.php 
// https://stackoverflow.com/questions/27902831/sqlite3-sqlstatehy000-general-error-5-database-is-locked
// https://www.sqlite.org/lockingv3.html#how_to_corrupt

/**
 * Wrapper class for PDO library.
 * 
 * 
 */
class Db
{
    /** @var array */
    private $activeDBConnections = [];

    /** @var string|null */
    private $connectionName = null;

    /** @var \PDO|null */
    private $reflectionPDO = null;
    
    /** @var \PDOStatement|null */
    private $reflectionPDOStatement = null;

    /** @var \PDOStatement|null */
    private $stm = null;

    /** @var array */
    private $connFlags = [
        \PDO::ATTR_PERSISTENT => true,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_TIMEOUT => 1,
    ];

    /** @var array */
    private $finalMethods = [
        'getColumnMeta',
        'columnCount',
        'rowCount',
    ];

    /** @var array */
    private $authMethods = [
        'where',
    ];

    /**
     * Generic method to handle diffrent DB drivers.
     *
     * @param ?string $connection `sqlite` or `sqlite:db_alias`, if it's null, the driver
     *                          will be get from config('db.default') field.
     * @return Db|\PDO
     */
    public function connect(string $connection = null): Db|\PDO
    {
        // Return PDO object by driver, if exists
        if (isset($this->activeDBConnections[$this->connectionName])) {
            return $this;
        }

        if (! is_null($connection) && ! in_array($connection, array_keys(config('db.connections')))) {
            throw new \PDOException(
                sprintf(
                    'ERROR[DB connection] DB connection "%s" not found.', 
                    $connection
                )
            );
        }

        $dbConfig = config('db');

        $this->connectionName = $connection ?? implode($dbConfig['default']);

        $this->activeDBConnections[$this->connectionName] = $this->resolveDbObject(
            $dbConfig['connections'][$this->connectionName]
        );

        return $this;
    }

    /**
     * Returns the last inserted id
     *
     * @return int|bool
     */
    public function lastInsertId(): int|bool
    {
        return $this->activeDBConnections[$this->connectionName]->lastInsertId();
    }

    /**
     * Pings selected connection.
     *
     * @return integer|false
     */
    public function ping(): int|false
    {
        $status = $this->activeDBConnections[$this->connectionName]->exec('SELECT 1');

        // Inmediately close connection if it's 'none'
        if ($this->connectionName == 'none') {
            $this->activeDBConnections[$this->connectionName] = null;
        }

        return $status;
    }

    /**
     * Returns the current active connections.
     *
     * @return array
     */
    public function getActiveDBConnections(): array
    {
        return $this->activeDBConnections;
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
        // PDO is null
        if (is_null($this->reflectionPDO)) {
            $this->reflectionPDO = new \ReflectionClass('PDO');
        }

        // Process PDO
        $result = $this->processReflection(
            $this->reflectionPDO, 
            $this->activeDBConnections[$this->connectionName], 
            $method, 
            $arguments
        );

        if (! is_null($result)) {
            if ($result instanceof \PDOStatement) {
                $this->stm = $result;
            }

            return $this;
        }

        // PDOStatement is null
        if (is_null($this->reflectionPDOStatement)) {
            $this->reflectionPDOStatement = new \ReflectionClass('PDOStatement');
        }

        // Process PDOStatement
        $result = $this->processReflection(
            $this->reflectionPDOStatement, 
            $this->stm, 
            $method, 
            $arguments
        );

        if (! is_null($result)) {
            if (in_array($method, $this->finalMethods)) {
                return $result;
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
     * Resolves the DB driver and connection.
     *
     * @param array $dbSetup
     * @return \PDO
     */
    private function resolveDbObject(array $dbSetup): \PDO
    {
        $dns = $dbSetup['driver'] . ":host=" . $dbSetup['server'] . ":" . $dbSetup['port'] .  ";dbname=" . $dbSetup['database'];

        // Exception for sqlite
        if ($dbSetup['driver'] == 'sqlite') {
            if (strtolower(pathinfo($dbSetup['database'])['extension']) !== 'sql') {
                throw new \InvalidArgumentException(
                    sprintf('ERROR[InvalidArgumentException] Database file name "%s" is not valid.', $dbSetup['database'])
                );
            }

            $dns = "sqlite:" . $dbSetup['server'] . "/" . $dbSetup['database'];
        }

        // Resolve PDO
        return new \PDO(
            $dns,
            $dbSetup['username'],
            $dbSetup['password'],
            $this->connFlags
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
