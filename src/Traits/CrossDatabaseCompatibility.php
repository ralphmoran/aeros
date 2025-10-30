<?php

namespace Aeros\Src\Traits;

/**
 * CrossDatabaseCompatibility Trait
 *
 * Handles driver-specific SQL syntax differences across MySQL, PostgreSQL, SQLite, and MSSQL.
 * Provides methods for identifier escaping, upsert queries, and other DB-specific operations.
 *
 * @package Aeros\Src\Traits
 */
trait CrossDatabaseCompatibility
{
    /**
     * Get the current database driver name.
     *
     * @return string Driver name: 'mysql', 'pgsql', 'sqlite', 'sqlsrv'
     */
    protected function getDriver(): string
    {
        static $driver = null;

        if ($driver === null) {
            $connection = $this->connectionName ?? config('db.default')[0];
            $driver = config("db.connections.{$connection}.driver");
        }

        return $driver;
    }

    /**
     * Quote/escape an identifier (table or column name) for the current driver.
     *
     * MySQL: `identifier`
     * PostgreSQL/SQLite: "identifier"
     * MSSQL: [identifier]
     *
     * @param string $identifier Table or column name
     * @return string Properly quoted identifier
     */
    protected function quoteIdentifier(string $identifier): string
    {
        // Remove any existing quotes first
        $identifier = trim($identifier, '`"[]');

        return match($this->getDriver()) {
            'mysql' => "`{$identifier}`",
            'pgsql', 'sqlite' => "\"{$identifier}\"",
            'sqlsrv' => "[{$identifier}]",
            default => $identifier
        };
    }

    /**
     * Quote multiple identifiers (for SELECT, INSERT, etc.).
     *
     * @param array $identifiers Array of column/table names
     * @return array Array of quoted identifiers
     */
    protected function quoteIdentifiers(array $identifiers): array
    {
        return array_map(fn($id) => $this->quoteIdentifier($id), $identifiers);
    }

    /**
     * Build a cross-database INSERT IGNORE / ON CONFLICT query.
     *
     * @param string $table Table name
     * @param array $columns Column names
     * @param array|null $conflictColumns Columns to check for conflicts (for PostgreSQL)
     * @return string SQL query
     */
    protected function buildUpsertQuery(string $table, array $columns, ?array $conflictColumns = null): string
    {
        $quotedTable = $this->quoteIdentifier($table);
        $quotedColumns = $this->quoteIdentifiers($columns);
        $columnList = implode(', ', $quotedColumns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return match($this->getDriver()) {
            'mysql' => "INSERT IGNORE INTO {$quotedTable} ({$columnList}) VALUES ({$placeholders})",

            'pgsql' => $this->buildPostgresUpsert($quotedTable, $quotedColumns, $placeholders, $conflictColumns),

            'sqlite' => "INSERT OR IGNORE INTO {$quotedTable} ({$columnList}) VALUES ({$placeholders})",

            'sqlsrv' => $this->buildMssqlUpsert($quotedTable, $quotedColumns, $placeholders, $conflictColumns),

            default => "INSERT INTO {$quotedTable} ({$columnList}) VALUES ({$placeholders})"
        };
    }

    /**
     * Build PostgreSQL-specific upsert with ON CONFLICT.
     *
     * @param string $table Quoted table name
     * @param array $columns Quoted column names
     * @param string $placeholders Placeholder string
     * @param array|null $conflictColumns Conflict columns
     * @return string SQL query
     */
    private function buildPostgresUpsert(string $table, array $columns, string $placeholders, ?array $conflictColumns): string
    {
        $columnList = implode(', ', $columns);

        if ($conflictColumns) {
            $conflictList = implode(', ', $this->quoteIdentifiers($conflictColumns));
            return "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders}) ON CONFLICT ({$conflictList}) DO NOTHING";
        }

        return "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders}) ON CONFLICT DO NOTHING";
    }

    /**
     * Build MSSQL-specific upsert with IF NOT EXISTS.
     *
     * @param string $table Quoted table name
     * @param array $columns Quoted column names
     * @param string $placeholders Placeholder string
     * @param array|null $conflictColumns Conflict columns
     * @return string SQL query
     */
    private function buildMssqlUpsert(string $table, array $columns, string $placeholders, ?array $conflictColumns): string
    {
        $columnList = implode(', ', $columns);

        if ($conflictColumns) {
            $whereClause = [];
            foreach ($conflictColumns as $col) {
                $whereClause[] = "{$this->quoteIdentifier($col)} = ?";
            }
            $where = implode(' AND ', $whereClause);

            return "IF NOT EXISTS (SELECT 1 FROM {$table} WHERE {$where}) "
                . "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})";
        }

        return "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})";
    }

    /**
     * Get the appropriate LIMIT clause for the current driver.
     *
     * @param int $limit Number of records
     * @param int|null $offset Offset
     * @return string LIMIT clause
     */
    protected function buildLimitClause(int $limit, ?int $offset = null): string
    {
        return match($this->getDriver()) {
            'mysql', 'pgsql', 'sqlite' => $offset !== null
                ? " LIMIT {$limit} OFFSET {$offset}"
                : " LIMIT {$limit}",

            'sqlsrv' => $offset !== null
                ? " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY"
                : " OFFSET 0 ROWS FETCH NEXT {$limit} ROWS ONLY",

            default => " LIMIT {$limit}"
        };
    }

    /**
     * Get database-specific NOW() function.
     *
     * @return string NOW function for current driver
     */
    protected function getNowFunction(): string
    {
        return match($this->getDriver()) {
            'mysql', 'sqlite' => 'CURRENT_TIMESTAMP',
            'pgsql' => 'NOW()',
            'sqlsrv' => 'GETDATE()',
            default => 'CURRENT_TIMESTAMP'
        };
    }

    /**
     * Get database-specific auto-increment syntax.
     *
     * @return string Auto-increment syntax
     */
    protected function getAutoIncrementSyntax(): string
    {
        return match($this->getDriver()) {
            'mysql' => 'AUTO_INCREMENT',
            'pgsql' => 'SERIAL',
            'sqlite' => 'AUTOINCREMENT',
            'sqlsrv' => 'IDENTITY(1,1)',
            default => 'AUTO_INCREMENT'
        };
    }

    /**
     * Build driver-specific query to get table columns.
     *
     * @param string $table Table name
     * @return string SQL query to fetch column information
     */
    protected function buildGetColumnsQuery(string $table): string
    {
        return match($this->getDriver()) {
            'mysql' => "SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",

            'pgsql' => "SELECT column_name FROM information_schema.columns 
                       WHERE table_schema = 'public' AND table_name = ?",

            'sqlite' => "PRAGMA table_info({$this->quoteIdentifier($table)})",

            'sqlsrv' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_NAME = ?",

            default => "SELECT * FROM {$this->quoteIdentifier($table)} LIMIT 0"
        };
    }

    /**
     * Parse column information from driver-specific result.
     *
     * @param array $result Result from getColumnsQuery
     * @return array Array of column names
     */
    protected function parseColumnNames(array $result): array
    {
        return match($this->getDriver()) {
            'mysql', 'pgsql', 'sqlsrv' => array_map(fn($row) => $row['COLUMN_NAME'] ?? $row['column_name'], $result),
            'sqlite' => array_map(fn($row) => $row['name'], $result),
            default => array_keys($result[0] ?? [])
        };
    }

    /**
     * Check if the current driver supports a specific feature.
     *
     * @param string $feature Feature name: 'json', 'cte', 'window_functions', 'full_text_search'
     * @return bool True if supported
     */
    protected function supportsFeature(string $feature): bool
    {
        $features = [
            'json' => ['mysql', 'pgsql', 'sqlsrv'],
            'cte' => ['mysql', 'pgsql', 'sqlsrv', 'sqlite'],
            'window_functions' => ['mysql', 'pgsql', 'sqlsrv', 'sqlite'],
            'full_text_search' => ['mysql', 'pgsql', 'sqlsrv', 'sqlite'],
            'recursive_cte' => ['mysql', 'pgsql', 'sqlsrv', 'sqlite'],
        ];

        return in_array($this->getDriver(), $features[$feature] ?? []);
    }
}
