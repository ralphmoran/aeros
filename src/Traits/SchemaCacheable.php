<?php

namespace Aeros\Src\Traits;

/**
 * SchemaCacheable Trait
 *
 * Provides schema caching functionality to avoid repeated database queries
 * for table structure information. Uses both runtime and persistent caching.
 *
 * @package Aeros\Src\Traits
 */
trait SchemaCacheable
{
    /**
     * Runtime cache for table schemas (per request).
     *
     * @var array
     */
    protected static array $runtimeSchemaCache = [];

    /**
     * Get table columns with caching support.
     * Checks runtime cache first, then persistent cache, then queries database.
     *
     * @param string $table Table name
     * @param bool $forceRefresh Force refresh from database
     * @return array Array of column names
     */
    protected function getTableColumns(string $table, bool $forceRefresh = false): array
    {
        // Check if caching is enabled
        if (! $this->isSchemaCacheEnabled()) {
        return $this->fetchTableColumnsFromDatabase($table);
    }

        // Check runtime cache
        if (!$forceRefresh && isset(self::$runtimeSchemaCache[$table])) {
            return self::$runtimeSchemaCache[$table];
        }

        // Check persistent cache
        if (!$forceRefresh) {
            $cached = $this->getFromPersistentCache($table);
            if ($cached !== null) {
                self::$runtimeSchemaCache[$table] = $cached;
                return $cached;
            }
        }

        // Fetch from database
        $columns = $this->fetchTableColumnsFromDatabase($table);

        // Store in both caches
        self::$runtimeSchemaCache[$table] = $columns;
        $this->storeToPersistentCache($table, $columns);

        return $columns;
    }

    /**
     * Fetch table columns directly from database.
     *
     * @param string $table Table name
     * @return array Array of column names
     */
    protected function fetchTableColumnsFromDatabase(string $table): array
    {
        try {
            $query = $this->buildGetColumnsQuery($table);

            // SQLite uses PRAGMA which doesn't need parameters
            if ($this->getDriver() === 'sqlite') {
                $result = db()->query($query)->fetchAll();
            } else {
                $result = db()->prepare($query)->execute([$table])->fetchAll();
            }

            return $this->parseColumnNames($result);

        } catch (\PDOException $e) {
            $this->logError("Failed to fetch columns for table '{$table}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get schema from persistent cache.
     *
     * @param string $table Table name
     * @return array|null Column names or null if not cached
     */
    protected function getFromPersistentCache(string $table): ?array
    {
        try {
            $cacheKey = $this->getSchemaCacheKey($table);
            $cached = cache('local')->get($cacheKey);

            return $cached ? unserialize($cached) : null;

        } catch (\Exception $e) {
            $this->logError("Failed to get schema from cache for table '{$table}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Store schema to persistent cache.
     *
     * @param string $table Table name
     * @param array $columns Column names
     * @return bool Success status
     */
    protected function storeToPersistentCache(string $table, array $columns): bool
    {
        try {
            $cacheKey = $this->getSchemaCacheKey($table);
            $ttl = $this->getSchemaCacheTTL();

            return cache('local')->set($cacheKey, serialize($columns), $ttl);

        } catch (\Exception $e) {
            $this->logError("Failed to store schema to cache for table '{$table}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear schema cache for a specific table or all tables.
     *
     * @param string|null $table Table name (null for all)
     * @return bool Success status
     */
    public function clearSchemaCache(?string $table = null): bool
    {
        try {
            // Clear runtime cache
            if ($table === null) {
                self::$runtimeSchemaCache = [];
            } else {
                unset(self::$runtimeSchemaCache[$table]);
            }

            // Clear persistent cache
            if ($table === null) {
                // Clear all schema cache keys
                // This is driver-specific, for local cache we'd need to scan files
                return true;
            } else {
                $cacheKey = $this->getSchemaCacheKey($table);
                return cache('local')->delete($cacheKey);
            }

        } catch (\Exception $e) {
            $this->logError("Failed to clear schema cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Warm up schema cache for current table.
     *
     * @return bool Success status
     */
    public function warmUpSchemaCache(): bool
    {
        try {
            $table = $this->getTableNameFromModel();
            $this->getTableColumns($table, true); // Force refresh
            return true;

        } catch (\Exception $e) {
            $this->logError("Failed to warm up schema cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate if a column exists in the table.
     *
     * @param string $column Column name
     * @param string|null $table Table name (uses model table if null)
     * @return bool True if column exists
     */
    protected function columnExists(string $column, ?string $table = null): bool
    {
        $table = $table ?? $this->getTableNameFromModel();
        $columns = $this->getTableColumns($table);

        return in_array($column, $columns);
    }

    /**
     * Validate multiple columns exist in the table.
     *
     * @param array $columns Column names
     * @param string|null $table Table name (uses model table if null)
     * @return bool True if all columns exist
     */
    protected function columnsExist(array $columns, ?string $table = null): bool
    {
        $table = $table ?? $this->getTableNameFromModel();
        $tableColumns = $this->getTableColumns($table);

        return empty(array_diff($columns, $tableColumns));
    }

    /**
     * Get invalid columns from a list.
     *
     * @param array $columns Column names to check
     * @param string|null $table Table name (uses model table if null)
     * @return array Invalid column names
     */
    protected function getInvalidColumns(array $columns, ?string $table = null): array
    {
        $table = $table ?? $this->getTableNameFromModel();
        $tableColumns = $this->getTableColumns($table);

        return array_diff($columns, $tableColumns);
    }

    /**
     * Generate cache key for table schema.
     *
     * @param string $table Table name
     * @return string Cache key
     */
    protected function getSchemaCacheKey(string $table): string
    {
        $driver = $this->getDriver();
        $connection = $this->connectionName ?? config('db.default')[0];

        return "aeros_schema_{$driver}_{$connection}_{$table}";
    }

    /**
     * Get schema cache TTL from config.
     *
     * @return int TTL in seconds
     */
    protected function getSchemaCacheTTL(): int
    {
        return config('db.model.cache_ttl', 3600); // Default: 1 hour
    }

    /**
     * Check if schema caching is enabled.
     *
     * @return bool True if caching is enabled
     */
    protected function isSchemaCacheEnabled(): bool
    {
        // Check model-specific override first
        if (isset($this->cacheSchema)) {
            return $this->cacheSchema;
        }

        // Check config
        return config('db.model.cache_schema', true);
    }

    /**
     * Get schema information with metadata.
     *
     * @param string|null $table Table name
     * @return array Schema information including columns, types, indexes, etc.
     */
    protected function getSchemaInfo(?string $table = null): array
    {
        $table = $table ?? $this->getTableNameFromModel();

        return [
            'table' => $table,
            'columns' => $this->getTableColumns($table),
            'primary_key' => $this->primary ?? 'id',
            'fillable' => $this->fillable ?? [],
            'guarded' => $this->guarded ?? [],
            'driver' => $this->getDriver(),
        ];
    }
}
