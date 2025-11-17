<?php

namespace Aeros\Src\Classes\Relationships;

use Aeros\Src\Classes\Model;

/**
 * HasManyThrough Relationship
 *
 * Represents a relationship through an intermediate model.
 *
 * Example:
 *   Country hasMany Users hasMany Posts
 *   Country hasManyThrough Posts (through Users)
 *
 *   - countries.id (local key)
 *   - users.country_id (first foreign key)
 *   - users.id (first local key / second foreign key)
 *   - posts.user_id (second foreign key)
 *   - posts.id (related key)
 *
 * @package Aeros\Src\Classes\Relations
 */
class HasManyThrough extends Relationship
{
    /**
     * The intermediate model instance.
     *
     * @var Model
     */
    protected Model $through;

    /**
     * The first foreign key (on intermediate table).
     *
     * @var string
     */
    protected string $firstKey;

    /**
     * The second foreign key (on related table).
     *
     * @var string
     */
    protected string $secondKey;

    /**
     * The local key on intermediate table.
     *
     * @var string
     */
    protected string $secondLocalKey;

    /**
     * Constructor.
     *
     * @param Model $parent Parent model instance
     * @param Model $related Related model instance
     * @param Model $through Intermediate model instance
     * @param string|null $firstKey First foreign key (auto-detected if null)
     * @param string|null $secondKey Second foreign key (auto-detected if null)
     * @param string|null $localKey Local key on parent (auto-detected if null)
     * @param string|null $secondLocalKey Local key on intermediate (auto-detected if null)
     */
    public function __construct(
        Model $parent,
        Model $related,
        Model $through,
        ?string $firstKey = null,
        ?string $secondKey = null,
        ?string $localKey = null,
        ?string $secondLocalKey = null
    ) {
        $this->through = $through;

        // Auto-detect keys
        $localKey = $localKey ?? $parent->getPrimaryKey();
        $this->firstKey = $firstKey ?? (strtolower(class_basename(get_class($parent))) . '_' . $localKey);
        $this->secondLocalKey = $secondLocalKey ?? $through->getPrimaryKey();
        $this->secondKey = $secondKey ?? (strtolower(class_basename(get_class($through))) . '_' . $this->secondLocalKey);

        // Call parent constructor
        parent::__construct($parent, $related, $this->secondKey, $localKey);
    }

    /**
     * Execute the relationship query and get the results.
     *
     * @return array Array of related models (empty array if none found)
     */
    public function get(): array
    {
        // Get the parent's local key value
        $localKeyValue = $this->parent->{$this->localKey};

        if (is_null($localKeyValue)) {
            return [];
        }

        // Build the query with two JOINs
        $parentTable = $this->parent->getTableNameFromModel();
        $throughTable = $this->through->getTableNameFromModel();
        $relatedTable = $this->related->getTableNameFromModel();

        // Build WHERE clause for additional constraints
        $additionalWhere = '';
        $whereValues = [$localKeyValue];

        if (!empty($this->constraints)) {
            $where = $this->buildWhereClause();
            $additionalWhere = ' AND ' . $where['sql'];
            $whereValues = array_merge($whereValues, $where['values']);
        }

        // Build ORDER BY and LIMIT
        $orderBy = $this->buildOrderByClause();
        $limit = $this->buildLimitClause();

        // Query with two JOINs
        $sql = "SELECT {$relatedTable}.*
                FROM {$relatedTable}
                INNER JOIN {$throughTable} 
                    ON {$relatedTable}.{$this->secondKey} = {$throughTable}.{$this->secondLocalKey}
                INNER JOIN {$parentTable}
                    ON {$throughTable}.{$this->firstKey} = {$parentTable}.{$this->localKey}
                WHERE {$parentTable}.{$this->localKey} = ?
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
            $models[] = $this->related->transformRecordToModel($result, get_class($this->related));
        }

        return $models;
    }

    /**
     * Get the intermediate (through) model.
     *
     * @return Model
     */
    public function getThrough(): Model
    {
        return $this->through;
    }
}
