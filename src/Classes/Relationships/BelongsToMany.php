<?php

namespace Aeros\Src\Classes\Relationships;

use Aeros\Src\Classes\Model;

/**
 * BelongsToMany Relationship
 *
 * Represents a many-to-many relationship using pivot table.
 * Auto-detects pivot table name using alphabetical convention.
 *
 * @package Aeros\Src\Classes\Relationships
 */
class BelongsToMany extends Relationship
{
    /**
     * The pivot table name.
     *
     * @var string
     */
    protected string $pivotTable;

    /**
     * The foreign pivot key (parent side of pivot).
     *
     * @var string
     */
    protected string $foreignPivotKey;

    /**
     * The related pivot key (related side of pivot).
     *
     * @var string
     */
    protected string $relatedPivotKey;

    /**
     * The related key (primary key on related table).
     *
     * @var string
     */
    protected string $relatedKey;

    /**
     * Pivot columns to include in results.
     *
     * @var array
     */
    protected array $pivotColumns = [];

    /**
     * Constructor.
     *
     * @param Model $parent Parent model instance
     * @param Model $related Related model instance
     * @param string|null $pivotTable Pivot table name (auto-detected if null)
     * @param string|null $foreignPivotKey Foreign pivot key (auto-detected if null)
     * @param string|null $relatedPivotKey Related pivot key (auto-detected if null)
     * @param string|null $localKey Local key (defaults to parent primary key)
     * @param string|null $relatedKey Related key (defaults to related primary key)
     */
    public function __construct(
        Model $parent,
        Model $related,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $localKey = null,
        ?string $relatedKey = null
    ) {
        // Auto-detect keys if not provided
        $localKey = $localKey ?? $parent->getPrimaryKey();
        $this->relatedKey = $relatedKey ?? $related->getPrimaryKey();

        // Get pivot table scheme
        $pivotScheme = Model::getPivotTableScheme(
            get_class($parent),
            get_class($related)
        );

        // Determine which column is for parent and which is for related
        $parentModelName = strtolower(class_basename(get_class($parent)));
        $relatedModelName = strtolower(class_basename(get_class($related)));

        // Remove trailing 's' for singular
        $parentModelName = rtrim($parentModelName, 's');
        $relatedModelName = rtrim($relatedModelName, 's');

        // Match columns to correct models
        $parentPivotKey = str_contains($pivotScheme['col1'], $parentModelName)
            ? $pivotScheme['col1']
            : $pivotScheme['col2'];

        $relatedPivotKeyAuto = str_contains($pivotScheme['col1'], $relatedModelName)
            ? $pivotScheme['col1']
            : $pivotScheme['col2'];

        $this->pivotTable = $pivotTable ?? $pivotScheme['name'];
        $this->foreignPivotKey = $foreignPivotKey ?? $parentPivotKey;
        $this->relatedPivotKey = $relatedPivotKey ?? $relatedPivotKeyAuto;

        // Call parent constructor
        parent::__construct($parent, $related, $this->relatedPivotKey, $localKey);
    }

    /**
     * Specify pivot columns to include in the results.
     *
     * @param array $columns Column names from pivot table
     * @return self
     */
    public function withPivot(array $columns): self
    {
        $this->pivotColumns = array_merge($this->pivotColumns, $columns);
        return $this;
    }

    /**
     * Execute the relationship query and get the results.
     *
     * @return array Array of related models (empty array if none found)
     */
    public function get(): array
    {
        $localKeyValue = $this->parent->{$this->localKey};

        if (is_null($localKeyValue)) {
            return [];
        }

        $relatedTable = $this->related->getTableNameFromModel();
        $relatedPrimary = $this->related->getPrimaryKey();

        // Select related table columns and pivot columns
        $pivotSelectCols = '';
        if (! empty($this->pivotColumns)) {
            $pivotSelectCols = ', ' . implode(', ', array_map(
                    fn($col) => "{$this->pivotTable}.{$col} as pivot_{$col}",
                    $this->pivotColumns
                ));
        }

        // Build WHERE clause for additional constraints on related table
        $additionalWhere = '';
        $whereValues = [$localKeyValue];

        if (! empty($this->constraints)) {
            $where = $this->buildWhereClause();
            $additionalWhere = ' AND ' . $where['sql'];
            $whereValues = array_merge($whereValues, $where['values']);
        }

        // Build ORDER BY and LIMIT
        $orderBy = $this->buildOrderByClause();
        $limit = $this->buildLimitClause();

        // Query: JOIN pivot table with related table
        $sql = "SELECT {$relatedTable}.*{$pivotSelectCols}
                FROM {$relatedTable}
                INNER JOIN {$this->pivotTable} 
                    ON {$relatedTable}.{$relatedPrimary} = {$this->pivotTable}.{$this->relatedPivotKey}
                WHERE {$this->pivotTable}.{$this->foreignPivotKey} = ?
                {$additionalWhere}
                {$orderBy}
                {$limit}";

        // Execute query
        $results = db()->prepare($sql)
            ->execute($whereValues)
            ->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($results)) {
            return [];
        }

        // Transform results to model instances
        $models = [];
        foreach ($results as $result) {
            // Separate pivot data from model data
            $pivotData = [];
            $modelData = [];

            foreach ($result as $key => $value) {
                if (str_starts_with($key, 'pivot_')) {
                    $pivotData[substr($key, 6)] = $value;
                } else {
                    $modelData[$key] = $value;
                }
            }

            // Create model instance
            $model = $this->related->transformRecordToModel($modelData, get_class($this->related));

            // Attach pivot data to model
            if (! empty($pivotData)) {
                $model->pivot = (object) $pivotData;
            }

            $models[] = $model;
        }

        return $models;
    }

    /**
     * Attach related models to the parent (create relationship in pivot table).
     *
     * @param int|array|Model $ids Related model ID(s) or model instance(s)
     * @param array $pivotData Additional pivot data to store
     * @return bool
     */
    public function attach($ids, array $pivotData = []): bool
    {
        $localKeyValue = $this->parent->{$this->localKey};

        if (is_null($localKeyValue)) {
            return false;
        }

        $ids = $this->normalizeIds($ids);

        if (empty($ids)) {
            return false;
        }

        $pivotCols = [$this->foreignPivotKey, $this->relatedPivotKey];
        $extraCols = array_keys($pivotData);
        $allCols = array_merge($pivotCols, $extraCols);

        $cols = implode(', ', $allCols);
        $placeholders = implode(', ', array_map(fn($col) => ":{$col}", $allCols));

        // Cross-database compatible upsert
        $driver = db()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        switch ($driver) {
            case 'mysql':
                $sql = "INSERT IGNORE INTO {$this->pivotTable} ({$cols}) VALUES ({$placeholders})";
                break;

            case 'pgsql':
                $sql = "INSERT INTO {$this->pivotTable} ({$cols}) VALUES ({$placeholders}) ON CONFLICT DO NOTHING";
                break;

            case 'sqlite':
                $sql = "INSERT OR IGNORE INTO {$this->pivotTable} ({$cols}) VALUES ({$placeholders})";
                break;

            case 'sqlsrv':
            case 'mssql':
                // Check if exists first, then insert
                $sql = "IF NOT EXISTS (SELECT 1 FROM {$this->pivotTable} WHERE {$this->foreignPivotKey} = :{$this->foreignPivotKey} AND {$this->relatedPivotKey} = :{$this->relatedPivotKey})
                    INSERT INTO {$this->pivotTable} ({$cols}) VALUES ({$placeholders})";
                break;

            default:
                $sql = "INSERT INTO {$this->pivotTable} ({$cols}) VALUES ({$placeholders})";
        }

        $stm = db()->prepare($sql);

        $success = true;

        foreach ($ids as $id) {
            $data = array_merge(
                [
                    $this->foreignPivotKey => $localKeyValue,
                    $this->relatedPivotKey => $id
                ],
                $pivotData
            );

            if (! $stm->execute($data)) {
                $success = false;
            }
        }

        $this->clearCachedRelationship();

        return $success;
    }

    /**
     * Detach related models from the parent (remove relationship from pivot table).
     *
     * @param int|array|Model|null $ids Related model ID(s), model instance(s), or null to detach all
     * @return bool
     */
    public function detach($ids = null): bool
    {
        $localKeyValue = $this->parent->{$this->localKey};

        if (is_null($localKeyValue)) {
            return false;
        }

        if (is_null($ids)) {
            $stm = db()->prepare(
                "DELETE FROM {$this->pivotTable} WHERE {$this->foreignPivotKey} = ?"
            )->execute([$localKeyValue]);

            return $stm->rowCount() ?: false;
        }

        $ids = $this->normalizeIds($ids);

        if (empty($ids)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $values = array_merge([$localKeyValue], $ids);

        $stm = db()->prepare(
            "DELETE FROM {$this->pivotTable} 
             WHERE {$this->foreignPivotKey} = ? 
             AND {$this->relatedPivotKey} IN ({$placeholders})"
        )->execute($values);

        $this->clearCachedRelationship();

        return $stm->rowCount() ?: false;
    }

    /**
     * Sync related models (replace all relationships with new set).
     *
     * @param array $ids Related model IDs
     * @param array $pivotData Additional pivot data
     * @return bool
     */
    public function sync(array $ids, array $pivotData = []): bool
    {
        $this->detach();

        return $this->attach($ids, $pivotData);
    }

    /**
     * Toggle related models (attach if not attached, detach if attached).
     *
     * @param int|array|Model $ids Related model ID(s) or model instance(s)
     * @return array ['attached' => [...], 'detached' => [...]]
     */
    public function toggle($ids): array
    {
        $ids = $this->normalizeIds($ids);
        $existing = $this->get();
        $existingIds = array_map(fn($model) => $model->{$this->relatedKey}, $existing);

        $toAttach = array_diff($ids, $existingIds);
        $toDetach = array_intersect($ids, $existingIds);

        if (! empty($toAttach)) {
            $this->attach($toAttach);
        }

        if (! empty($toDetach)) {
            $this->detach($toDetach);
        }

        $this->clearCachedRelationship();

        return [
            'attached' => array_values($toAttach),
            'detached' => array_values($toDetach)
        ];
    }

    /**
     * Normalize IDs from various input types.
     *
     * @param mixed $ids
     * @return array
     */
    protected function normalizeIds($ids): array
    {
        if ($ids instanceof Model) {
            return [$ids->{$this->relatedKey}];
        }

        if (is_array($ids)) {
            return array_map(function ($id) {
                return $id instanceof Model ? $id->{$this->relatedKey} : $id;
            }, $ids);
        }

        return [$ids];
    }

    /**
     * Get the pivot table name.
     *
     * @return string
     */
    public function getPivotTable(): string
    {
        return $this->pivotTable;
    }

    /**
     * Clear cached relationship data on parent model.
     *
     * @return void
     */
    protected function clearCachedRelationship(): void
    {
        $relationName = null;

        // Find the relationship method name
        foreach (get_class_methods($this->parent) as $method) {
            if (method_exists($this->parent, $method)) {
                try {
                    $relation = $this->parent->$method();
                    if ($relation instanceof BelongsToMany && $relation->getPivotTable() === $this->getPivotTable()) {
                        $relationName = $method;
                        break;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        // Clear using the Model's method
        if ($relationName && method_exists($this->parent, 'clearRelationCache')) {
            $this->parent->clearRelationCache($relationName);
        }
    }
}
