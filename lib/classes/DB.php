<?php

namespace Classes;

/**
 * See https://www.php.net/manual/en/class.pdo.php
 * 
 * @method \PDO __construct — Creates a PDO instance representing a connection 
 *                            to a database
 * @method bool beginTransaction() — Initiates a transaction
 * @method bool commit() — Commits a transaction
 * @method ?string errorCode() — Fetch the SQLSTATE associated with the last 
 *                              operation on the database handle
 * @method array errorInfo() — Fetch extended error information associated with 
 *                              the last operation on the database handle
 * @method int|false exec() — Execute an SQL statement and return the number of 
 *                              affected rows
 * @method mixed getAttribute() — Retrieve a database connection attribute
 * @method static array getAvailableDrivers() — Return an array of available PDO 
 *                              drivers
 * @method bool inTransaction() — Checks if inside a transaction
 * @method string|false lastInsertId() — Returns the ID of the last inserted row 
 *                              or sequence value
 * @method PDOStatement|false prepare() — Prepares a statement for execution and 
 *                              returns a statement object
 * @method PDOStatement|false query() — Prepares and executes an SQL statement 
 *                              without placeholders
 * @method string|false quote() — Quotes a string for use in a query
 * @method bool rollBack() — Rolls back a transaction
 * @method bool setAttribute() — Set an attribute
 */

class Db
{
    /** @var string */
    private $driver = '';

    /** @var \PDO */
    private $dbObject = null;

    /**
     * Generic method to handle diffrent DB drivers.
     *
     * @param string $driver
     * @return \PDO
     */
    public function connect(string $driver = ''): \PDO
    {
        $dbSetup = config('db');

        $this->driver = $driver = $driver ?: $dbSetup['default'];

        switch ($driver) {
            case 'postgres':
                return $this->dbObject = $this->gePostgresPDO();
                break;
            case 'sqlite':
                return $this->dbObject = $this->getSqlitePDO();
                break;
            case 'mysql':
                return $this->dbObject = $this->getMysqlPDO();
                break;
            default:
                throw new \PDOException(
                    sprintf('ERROR[PDOException] DB Driver "%s" not found or invalid.', $driver)
                );
        }
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

        return new \PDO("sqlite:" . $dbSetup['server'] . "/" . $dbSetup['database'] . ".sql");
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
