<?php

namespace Aeros\Src\Classes\Relationships;

use Aeros\Src\Classes\Model;

/**
 * Base Relationship Class
 *
 * Provides query building and method chaining capabilities for relationships.
 * All specific relationship types (HasOne, HasMany, etc.) extend this class.
 *
 * @package Aeros\Src\Classes\Relations
 */
abstract class Relationship
{
    /**
     * The parent model instance.
     *
     * @var Model
     */
    protected Model $parent;

    /**
     * The related model instance.
     *
     * @var Model
     */
    protected Model $related;

    /**
     * The foreign key of the relationship.
     *
     * @var string
     */
    protected string $foreignKey;

    /**
     * The local key of the relationship.
     *
     * @var string
     */
    protected string $localKey;

    /**
     * Additional WHERE constraints for the relationship query.
     *
     * @var array
     */
    protected array $constraints = [];

    /**
     * ORDER BY clause.
     *
     * @var array
     */
    protected array $orderBy = [];

    /**
     * LIMIT clause.
     *
     * @var ?int
     */
    protected ?int $limit = null;

    /**
     * OFFSET clause.
     *
     * @var ?int
     */
    protected ?int $offset = null;

    /**
     * Constructor.
     *
     * @param Model $parent Parent model instance
     * @param Model $related Related model instance
     * @param string $foreignKey Foreign key column
     * @param string $localKey Local key column
     */
    public function __construct(Model $parent, Model $related, string $foreignKey, string $localKey)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * Add a WHERE constraint to the relationship query.
     *
     * @param string|array $column Column name or array of conditions
     * @param mixed $operator Operator or value if no operator
     * @param mixed $value Value to compare (optional)
     * @param string $logical Logical operator (AND/OR)
     * @return self
     */
    public function where($column, $operator = null, $value = null, string $logical = 'AND'): self
    {
        // Handle different argument patterns
        if (is_array($column)) {
            // where(['status' => 'active'])
            foreach ($column as $key => $val) {
                $this->constraints[] = [$key, '=', $val, $logical];
            }
        } elseif (func_num_args() === 2) {
            // where('status', 'active') - assumes '=' operator
            $this->constraints[] = [$column, '=', $operator, $logical];
        } else {
            // where('status', '=', 'active')
            $this->constraints[] = [$column, $operator, $value, $logical];
        }

        return $this;
    }

    /**
     * Add an OR WHERE constraint to the relationship query.
     *
     * @param string|array $column Column name or array of conditions
     * @param mixed $operator Operator or value if no operator
     * @param mixed $value Value to compare (optional)
     * @return self
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column Column name
     * @param string $direction Sort direction (ASC/DESC)
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$column, strtoupper($direction)];
        return $this;
    }

    /**
     * Set the LIMIT clause.
     *
     * @param int $limit Number of records to return
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the OFFSET clause.
     *
     * @param int $offset Number of records to skip
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Alias for limit().
     *
     * @param int $count Number of records to return
     * @return self
     */
    public function take(int $count): self
    {
        return $this->limit($count);
    }

    /**
     * Get the first result.
     *
     * @return Model|null
     */
    public function first(): ?Model
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * Count the number of related records.
     *
     * @return int
     */
    public function count(): int
    {
        $results = $this->get();
        return is_array($results) ? count($results) : (is_null($results) ? 0 : 1);
    }

    /**
     * Check if any related records exist.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Build the WHERE clause string and bound values.
     *
     * @param array $additionalConstraints Additional constraints to merge
     * @return array ['sql' => string, 'values' => array]
     */
    protected function buildWhereClause(array $additionalConstraints = []): array
    {
        $allConstraints = array_merge($additionalConstraints, $this->constraints);

        if (empty($allConstraints)) {
            return ['sql' => '', 'values' => []];
        }

        $sql = '';
        $values = [];
        $operator = '';

        foreach ($allConstraints as $constraint) {
            [$column, $op, $value, $logical] = array_pad($constraint, 4, 'AND');

            $operator = $logical;
            $sql .= "{$column} {$op} ? {$logical} ";
            $values[] = $value;
        }

        // Remove trailing logical operator
        $sql = rtrim($sql, " AND OR ");

        return ['sql' => $sql, 'values' => $values];
    }

    /**
     * Build the ORDER BY clause string.
     *
     * @return string
     */
    protected function buildOrderByClause(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        $clauses = array_map(
            fn($order) => "{$order[0]} {$order[1]}",
            $this->orderBy
        );

        return ' ORDER BY ' . implode(', ', $clauses);
    }

    /**
     * Build the LIMIT clause string.
     *
     * @return string
     */
    protected function buildLimitClause(): string
    {
        if ($this->limit === null && $this->offset === null) {
            return '';
        }

        $driver = db()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlsrv' || $driver === 'mssql') {
            // MSSQL requires ORDER BY for OFFSET/FETCH
            $clause = '';

            if ($this->offset !== null) {
                $clause .= " OFFSET {$this->offset} ROWS";
            } else {
                $clause .= " OFFSET 0 ROWS";
            }

            if ($this->limit !== null) {
                $clause .= " FETCH NEXT {$this->limit} ROWS ONLY";
            }

            return $clause;
        }

        // MySQL, PostgreSQL, SQLite
        $clause = '';

        if ($this->limit !== null) {
            $clause .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $clause .= " OFFSET {$this->offset}";
        }

        return $clause;
    }

    /**
     * Execute the relationship query and get results.
     * This must be implemented by each relationship type.
     *
     * @return Model|array|null
     */
    abstract public function get();

    /**
     * Get the parent model.
     *
     * @return Model
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * Get the related model.
     *
     * @return Model
     */
    public function getRelated(): Model
    {
        return $this->related;
    }

    /**
     * Get the foreign key.
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the local key.
     *
     * @return string
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * Get the table name from the related model.
     *
     * @return string
     */
    protected function getRelatedTable(): string
    {
        return $this->related->getTableNameFromModel();
    }
}
