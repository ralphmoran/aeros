<?php

namespace Aeros\Src\Classes\Relationships;

use Aeros\Src\Classes\Model;

/**
 * HasMany Relationship
 *
 * Represents a one-to-many relationship using pivot table.
 * Auto-detects pivot table name using alphabetical convention.
 *
 * @package Aeros\Src\Classes\Relationships
 */
class HasMany extends Relationship
{
    /**
     * Pivot table name.
     *
     * @var string
     */
    protected string $pivotTable;

    /**
     * Foreign pivot key (parent side).
     *
     * @var string
     */
    protected string $foreignPivotKey;

    /**
     * Related pivot key (related side).
     *
     * @var string
     */
    protected string $relatedPivotKey;

    /**
     * Constructor.
     *
     * @param Model $parent
     * @param Model $related
     * @param string $foreignKey Not used for pivot (kept for compatibility)
     * @param string $localKey
     * @param string|null $pivotTable Optional manual override
     */
    public function __construct(Model $parent, Model $related, string $foreignKey, string $localKey, ?string $pivotTable = null)
    {
        parent::__construct($parent, $related, $foreignKey, $localKey);

        // Auto-detect pivot table using Model's convention
        $pivotScheme = Model::getPivotTableScheme(
            get_class($parent),
            get_class($related)
        );

        $parentModelName = strtolower(class_basename(get_class($parent)));
        $relatedModelName = strtolower(class_basename(get_class($related)));
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
        $this->foreignPivotKey = $parentPivotKey;
        $this->relatedPivotKey = $relatedPivotKeyAuto;
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

        // Build WHERE clause for additional constraints
        $additionalWhere = '';
        $whereValues = [$localKeyValue];

        if (! empty($this->constraints)) {
            $where = $this->buildWhereClause();
            $additionalWhere = ' AND ' . $where['sql'];
            $whereValues = array_merge($whereValues, $where['values']);
        }

        $orderBy = $this->buildOrderByClause();
        $limit = $this->buildLimitClause();

        // Query through pivot table
        $sql = "SELECT {$relatedTable}.*
                FROM {$relatedTable}
                INNER JOIN {$this->pivotTable} 
                    ON {$relatedTable}.{$relatedPrimary} = {$this->pivotTable}.{$this->relatedPivotKey}
                WHERE {$this->pivotTable}.{$this->foreignPivotKey} = ?
                {$additionalWhere}
                {$orderBy}
                {$limit}";

        $results = db()->prepare($sql)
            ->execute($whereValues)
            ->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($results)) {
            return [];
        }

        // Transform results to model instances
        $models = [];
        foreach ($results as $result) {
            $models[] = $this->related->transformRecordToModel($result, get_class($this->related));
        }

        return $models;
    }
}
