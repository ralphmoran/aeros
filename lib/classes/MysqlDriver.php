<?php

namespace Classes;

use Traits\MagicMethods;
use Interfaces\DBHandlerInterface;

class MySQLDriver extends Singleton implements DBHandlerInterface
{
    use MagicMethods;

    /** @var MySQLi object */
    private $conn;

    /** @var resultset $rs */
    private $rs = null;

    /** @var int RO */
    const RO = 0;

    /** @var int CUD */
    const CUD = 1;

    /**
     * Initiates connection to the database and turns off automatic commits.
     *
     * @return void
     */
    protected function __construct()
    {
        try {
            if (is_null($this->conn)) {
                $this->connect();
            }
        } catch (\Exception $e) {
            printf($e->getMessage());
        }
    }

    /**
     * Stablishes connection to databases.
     * 
     * @param int $type
     * @return void
     */
    public function connect(int $type = self::RO)
    {
        if ($type == $this::RO) {
            $this->conn = new \mysqli(
                env('RO_DB_SERVER'),
                env('RO_DB_USERNAME'),
                env('RO_DB_PASSWORD'),
                env('RO_DB_DATABASE'),
                env('RO_DB_PORT')
            );
        }

        if ($type == $this::CUD) {
            $this->conn = new \mysqli(
                env('DB_SERVER'),
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                env('DB_DATABASE'),
                env('DB_PORT')
            );
        }

        if ($this->conn->connect_errno) {
            throw new \Exception(
                sprintf(
                    'ERROR[MySQL:%s] Failed to connect to MySQL: %s',
                    $this->conn->connect_errno,
                    $this->conn->connect_error
                )
            );

            die();
        }

        $this->conn->autocommit(false);
    }

    /**
     * Selects the DB name to work on.
     *
     * @param string $db_name
     * @return void
     */
    public function select_db(string $db_name)
    {
        $this->conn->select_db($db_name);

        return $this;
    }

    /**
     * Runs mul queeries on a single call.
     *
     * @param string $multi_query
     * @return void
     */
    public function multi_query(string $multi_query)
    {
        $this->conn->multi_query($multi_query);

        return $this;
    }

    /**
     * Runs a SQL query against the database.
     *
     * @param string $q
     * @return mixed
     */
    public function query(string $q)
    {
        $auth_action = false;

        if (str_find($q, ['INSERT INTO', 'UPDATE', 'DELETE FROM', 'CREATE'])) {
            $this->connect($this::CUD);
            $auth_action = true;
        }

        if (str_find($q, ['SELECT'])) {
            $auth_action = true;
        }

        if (!$auth_action) {
            printf("ERROR[SQL Format] The current query string is invalid.<br/>");
            return false;
        }

        if (!$rs = $this->conn->query($q)) {
            printf("ERROR[SQL] %s<br/>", $this->error());
            return false;
        }

        $this->rs = $rs;

        return $this;
    }

    /**
     * Fetches the next data row from a resultset.
     *
     * @param int $mode
     * @return array|null|false
     */
    public function fetch(int $mode = MYSQLI_ASSOC)
    {
        return $this->rs->fetch_array($mode);
    }

    /**
     * Returns the number of affected/found rows.
     *
     * @return void
     */
    public function num_rows()
    {
        return $this->rs->num_rows;
    }

    /**
     * Stores result from last query, if succed.
     *
     * @return void
     */
    public function store_result()
    {
        return $this->conn->store_result();
    }

    /**
     * Returns error from the las query.
     *
     * @return void
     */
    public function error()
    {
        return $this->conn->error;
    }

    /**
     * Moves the MySQLi pointer to the next result if many queries were executed.
     *
     * @return void
     */
    public function next_result()
    {
        $this->conn->next_result();
    }

    /**
     * Commits last query transaction: INSERT, UPDATE, DELETE actions.
     *
     * @return bool
     */
    public function commit(): bool
    {
        try {
            if (!$this->conn->commit()) {
                throw new \Exception(
                    sprintf('ERROR[Commit] Commit transaction failed.')
                );

                $this->conn->rollback();
            }

            // Last query execution
            if (!$this->rs) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            printf($e->getMessage());
        }
    }

    /**
     * Returns last autoincremented ID from INSERT or UPDATE clause.
     *
     * @return integer
     */
    public function get_last_insert_id(): int
    {
        return $this->conn->insert_id;
    }

    /**
     * Escapes special characters.
     *
     * @param string $string
     * @return string
     */
    public function escape(string $string)
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Closes connection.
     *
     * @return void
     */
    public function close()
    {
        $this->conn->close();
    }
}
