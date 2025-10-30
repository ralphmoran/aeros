<?php

namespace Aeros\Src\Traits;

/**
 * QueryBuilder Trait
 *
 * Provides fluent query building capabilities with support for:
 * - WHERE clauses (simple and complex)
 * - JOINs (INNER, LEFT, RIGHT, CROSS)
 * - ORDER BY, GROUP BY, HAVING
 * - LIMIT, OFFSET
 * - UNION, UNION ALL
 * - Subqueries
 * - Raw queries
 *
 * Maintains backwards compatibility with existing Aeros Model find() method.
 *
 * @package Aeros\Src\Traits
 */
trait QueryBuilder
{
    /**
     * Query builder state
     */
    protected array $queryWhere = [];
    protected array $queryJoins = [];
    protected array $queryOrderBy = [];
    protected array $queryGroupBy = [];
    protected array $queryHaving = [];
    protected array $queryBindings = [];
    protected ?int $queryLimit = null;
    protected ?int $queryOffset = null;
    protected ?string $querySelect = null;
    protected array $queryUnions = [];
    protected bool $isQueryBuilder = false;

    /**
     * Start a new query builder instance.
     *
     * Usage: User::query()->where('status', 'active')->get();
     *
     * @return self
     */
    public static function query(): self
    {
        $instance = new static();
        $instance->isQueryBuilder = true;
        $instance->resetQueryBuilder();

        return $instance;
    }

    /**
     * Add a WHERE clause.
     *
     * Usage:
     *   ->where('email', 'test@test.com')
     *   ->where('age', '>', 18)
     *   ->where('status', 'IN', ['active', 'pending'])
     *
     * @param string $column Column name
     * @param mixed $operatorOrValue Operator or value
     * @param mixed $value Value (if operator is provided)
     * @param string $boolean AND or OR
     * @return self
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null, string $boolean = 'AND'): self
    {
        // Validate column exists
        if (! $this->columnExists($column)) {
            throw new \InvalidArgumentException("Column '{$column}' does not exist in table '{$this->getTableNameFromModel()}'");
        }

        $operator = strtoupper($operatorOrValue);

        // If only 2 arguments, assume equals
        if ($value === null) {
            $value = $operatorOrValue;
            $operator = '=';
        }

        // Validate operator
        $this->validateOperator($operator);

        $this->queryWhere[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => strtoupper($boolean)
        ];

        return $this;
    }

    /**
     * Add an OR WHERE clause.
     *
     * @param string $column
     * @param mixed $operatorOrValue
     * @param mixed $value
     * @return self
     */
    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return $this->where($column, $operatorOrValue, $value, 'OR');
    }

    /**
     * Add a WHERE IN clause.
     *
     * @param string $column Column name
     * @param array $values Array of values
     * @param string $boolean AND or OR
     * @return self
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->where($column, 'IN', $values, $boolean);
    }

    /**
     * Add a WHERE NOT IN clause.
     *
     * @param string $column Column name
     * @param array $values Array of values
     * @param string $boolean AND or OR
     * @return self
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->where($column, 'NOT IN', $values, $boolean);
    }

    /**
     * Add a WHERE NULL clause.
     *
     * @param string $column Column name
     * @param string $boolean AND or OR
     * @return self
     */
    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        return $this->where($column, 'IS NULL', null, $boolean);
    }

    /**
     * Add a WHERE NOT NULL clause.
     *
     * @param string $column Column name
     * @param string $boolean AND or OR
     * @return self
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        return $this->where($column, 'IS NOT NULL', null, $boolean);
    }

    /**
     * Add a WHERE BETWEEN clause.
     *
     * @param string $column Column name
     * @param mixed $start Start value
     * @param mixed $end End value
     * @param string $boolean AND or OR
     * @return self
     */
    public function whereBetween(string $column, mixed $start, mixed $end, string $boolean = 'AND'): self
    {
        return $this->where($column, 'BETWEEN', [$start, $end], $boolean);
    }

    /**
     * Add a JOIN clause.
     *
     * Usage:
     *   ->join('posts', 'users.id', '=', 'posts.user_id')
     *   ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @param string $type JOIN type: INNER, LEFT, RIGHT, CROSS
     * @return self
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->queryJoins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => strtoupper($type)
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return self
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN clause.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return self
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add a CROSS JOIN clause.
     *
     * @param string $table
     * @return self
     */
    public function crossJoin(string $table): self
    {
        $this->queryJoins[] = [
            'table' => $table,
            'type' => 'CROSS'
        ];

        return $this;
    }

    /**
     * Add ORDER BY clause.
     *
     * Usage:
     *   ->orderBy('created_at', 'DESC')
     *   ->orderBy('name') // Defaults to ASC
     *
     * @param string $column Column name
     * @param string $direction ASC or DESC
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        if (! $this->columnExists($column)) {
            throw new \InvalidArgumentException("Column '{$column}' does not exist in table '{$this->getTableNameFromModel()}'");
        }

        $direction = strtoupper($direction);

        if (! in_array($direction, ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException("Invalid ORDER BY direction: {$direction}");
        }

        $this->queryOrderBy[] = [
            'column' => $column,
            'direction' => $direction
        ];

        return $this;
    }

    /**
     * Add GROUP BY clause.
     *
     * @param string|array $columns Column name(s)
     * @return self
     */
    public function groupBy(string|array $columns): self
    {
        $columns = is_array($columns) ? $columns : [$columns];

        foreach ($columns as $column) {

            if (! $this->columnExists($column)) {
                throw new \InvalidArgumentException("Column '{$column}' does not exist in table '{$this->getTableNameFromModel()}'");
            }

            $this->queryGroupBy[] = $column;
        }

        return $this;
    }

    /**
     * Add HAVING clause (must be used with groupBy).
     *
     * @param string $column Column name
     * @param mixed $operatorOrValue Operator or value
     * @param mixed $value Value
     * @return self
     */
    public function having(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        $operator = strtoupper($operatorOrValue);

        if ($value === null) {
            $value = $operatorOrValue;
            $operator = '=';
        }

        $this->validateOperator($operator);

        $this->queryHaving[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * Set LIMIT.
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->queryLimit = $limit;
        return $this;
    }

    /**
     * Set OFFSET.
     *
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->queryOffset = $offset;
        return $this;
    }

    /**
     * Alias for limit().
     *
     * @param int $count
     * @return self
     */
    public function take(int $count): self
    {
        return $this->limit($count);
    }

    /**
     * Alias for offset().
     *
     * @param int $count
     * @return self
     */
    public function skip(int $count): self
    {
        return $this->offset($count);
    }

    /**
     * Select specific columns.
     *
     * @param string|array $columns
     * @return self
     */
    public function select(string|array $columns = '*'): self
    {
        if (is_array($columns)) {
            $columns = implode(', ', $columns);
        }

        $this->querySelect = $columns;

        return $this;
    }

    /**
     * Add a UNION query.
     *
     * @param \Closure|string $query Closure returning query builder or raw SQL
     * @param bool $all Use UNION ALL
     * @return self
     */
    public function union(\Closure|string $query, bool $all = false): self
    {
        $this->queryUnions[] = [
            'query' => $query,
            'all' => $all
        ];

        return $this;
    }

    /**
     * Execute the query and get results.
     *
     * @return array
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();

        try {
            $result = db()->prepare($sql)
                ->execute($this->queryBindings)
                ->fetchAll();

            $this->resetQueryBuilder();

            // Transform results to model instances if configured
            if ($this->shouldTransformToModels()) {
                return array_map(
                    fn($record) => $this->transformRecordToModel($record, get_class($this)),
                    $result
                );
            }

            return $result ?: [];

        } catch (\PDOException $e) {
            $this->handleQueryException($e, $sql);
            return [];
        }
    }

    /**
     * Get first result.
     *
     * @return mixed
     */
    public function first(): mixed
    {
        $this->limit(1);

        return $this->get()[0] ?? null;
    }

    /**
     * Get count of records.
     *
     * @return int
     */
    public function count(): int
    {
        $originalSelect = $this->querySelect;
        $this->querySelect = 'COUNT(*) as total';

        $result = $this->first();
        $this->querySelect = $originalSelect;

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Check if records exist.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Execute raw SQL query.
     *
     * @param string $sql Raw SQL query
     * @param array $bindings Parameter bindings
     * @return array
     */
    public function raw(string $sql, array $bindings = []): array
    {
        try {
            return db()->prepare($sql)
                ->execute($bindings)
                ->fetchAll() ?: [];

        } catch (\PDOException $e) {
            $this->handleQueryException($e, $sql);
            return [];
        }
    }

    /**
     * Build complete SELECT query from query builder state.
     *
     * @return string
     */
    protected function buildSelectQuery(): string
    {
        $table = $this->quoteIdentifier($this->getTableNameFromModel());
        $select = $this->querySelect ?? '*';

        $sql = "SELECT {$select} FROM {$table}";

        // Add JOINs
        if (! empty($this->queryJoins)) {
            $sql .= $this->buildJoinClause();
        }

        // Add WHERE
        if (! empty($this->queryWhere)) {
            $sql .= $this->buildWhereClause();
        }

        // Add GROUP BY
        if (! empty($this->queryGroupBy)) {
            $sql .= $this->buildGroupByClause();
        }

        // Add HAVING
        if (! empty($this->queryHaving)) {
            $sql .= $this->buildHavingClause();
        }

        // Add UNION
        if (! empty($this->queryUnions)) {
            $sql .= $this->buildUnionClause();
        }

        // Add ORDER BY
        if (! empty($this->queryOrderBy)) {
            $sql .= $this->buildOrderByClause();
        }

        // Add LIMIT/OFFSET
        if ($this->queryLimit !== null) {
            $sql .= $this->buildLimitClause($this->queryLimit, $this->queryOffset);
        }

        return $sql;
    }

    /**
     * Build WHERE clause from query state.
     *
     * @return string
     */
    protected function buildWhereClause(): string
    {
        $conditions = [];
        $this->queryBindings = [];

        foreach ($this->queryWhere as $index => $where) {
            $column = $this->quoteIdentifier($where['column']);
            $operator = $where['operator'];
            $value = $where['value'];
            $boolean = $index === 0 ? '' : " {$where['boolean']} ";

            $this->handleOperator($operator, $column, $value, $boolean, $conditions);
        }

        return ' WHERE ' . implode('', $conditions);
    }

    /**
     * Build JOIN clause.
     *
     * @return string
     */
    protected function buildJoinClause(): string
    {
        $joins = [];

        foreach ($this->queryJoins as $join) {
            $table = $this->quoteIdentifier($join['table']);

            if ($join['type'] !== 'CROSS') {
                $joins[] = " CROSS JOIN {$table}";

                continue;
            }

            $first = $this->quoteIdentifier($join['first']);
            $second = $this->quoteIdentifier($join['second']);
            $joins[] = " {$join['type']} JOIN {$table} ON {$first} {$join['operator']} {$second}";
        }

        return implode('', $joins);
    }

    /**
     * Build ORDER BY clause.
     *
     * @return string
     */
    protected function buildOrderByClause(): string
    {
        $orders = [];

        foreach ($this->queryOrderBy as $order) {
            $column = $this->quoteIdentifier($order['column']);
            $orders[] = "{$column} {$order['direction']}";
        }

        return ' ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Build GROUP BY clause.
     *
     * @return string
     */
    protected function buildGroupByClause(): string
    {
        $columns = array_map(
            fn($col) => $this->quoteIdentifier($col),
            $this->queryGroupBy
        );

        return ' GROUP BY ' . implode(', ', $columns);
    }

    /**
     * Build HAVING clause.
     *
     * @return string
     */
    protected function buildHavingClause(): string
    {
        $conditions = [];

        foreach ($this->queryHaving as $having) {
            $column = $this->quoteIdentifier($having['column']);
            $conditions[] = "{$column} {$having['operator']} ?";
            $this->queryBindings[] = $having['value'];
        }

        return ' HAVING ' . implode(' AND ', $conditions);
    }

    /**
     * Build UNION clause.
     *
     * @return string
     */
    protected function buildUnionClause(): string
    {
        $unions = [];

        foreach ($this->queryUnions as $union) {
            $unionType = $union['all'] ? 'UNION ALL' : 'UNION';

            if ($union['query'] instanceof \Closure) {
                $subQuery = $union['query'](static::query());
                $unions[] = " {$unionType} ({$subQuery->buildSelectQuery()})";
                $this->queryBindings = array_merge($this->queryBindings, $subQuery->queryBindings);

                continue;
            }

            $unions[] = " {$unionType} ({$union['query']})";
        }

        return implode('', $unions);
    }

    /**
     * Validate SQL operator.
     *
     * @param string $operator
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateOperator(string $operator): void
    {
        $validOperators = [
            '=', '!=', '<>', '<', '>', '<=', '>=',
            'LIKE', 'NOT LIKE', 'ILIKE',
            'IN', 'NOT IN',
            'BETWEEN', 'NOT BETWEEN',
            'IS NULL', 'IS NOT NULL',
            'EXISTS', 'NOT EXISTS'
        ];

        if (! in_array(strtoupper($operator), $validOperators)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }
    }

    /**
     * Reset query builder state.
     *
     * @return void
     */
    protected function resetQueryBuilder(): void
    {
        $this->queryWhere = [];
        $this->queryJoins = [];
        $this->queryOrderBy = [];
        $this->queryGroupBy = [];
        $this->queryHaving = [];
        $this->queryBindings = [];
        $this->queryLimit = null;
        $this->queryOffset = null;
        $this->querySelect = null;
        $this->queryUnions = [];
        $this->isQueryBuilder = false;
    }

    /**
     * Check if results should be transformed to model instances.
     *
     * @return bool
     */
    protected function shouldTransformToModels(): bool
    {
        return config('db.model.transform_results', false);
    }

    /**
     * Handle query exception.
     *
     * @param \PDOException $e
     * @param string $sql
     * @return void
     */
    protected function handleQueryException(\PDOException $e, string $sql): void
    {
        $this->logError("Query failed: {$sql}. Error: " . $e->getMessage());

        if ($this->shouldThrowExceptions()) {
            throw $e;
        }
    }

    /**
     * @param string $operator
     * @param string $column
     * @param mixed $value
     * @param string $boolean
     * @param array $conditions
     * @return void
     */
    private function handleOperator(string $operator, string $column, mixed $value, string $boolean, array &$conditions): void
    {
        match ($operator) {
            'IN', 'NOT IN' => $this->handleInOperator($operator, $column, $value, $boolean, $conditions),
            'IS NULL', 'IS NOT NULL' => $conditions[] = "{$boolean}{$column} {$operator}",
            'BETWEEN' => $this->handleBetweenOperator($column, $value, $boolean, $conditions),
            default => $this->handleDefaultOperator($operator, $column, $value, $boolean, $conditions)
        };
    }

    /**
     * @param string $operator
     * @param string $column
     * @param mixed $value
     * @param string $boolean
     * @param array $conditions
     * @return void
     */
    private function handleInOperator(string $operator, string $column, mixed $value, string $boolean, array &$conditions): void
    {
        $placeholders = implode(', ', array_fill(0, count($value), '?'));
        $conditions[] = "{$boolean}{$column} {$operator} ({$placeholders})";
        $this->queryBindings = array_merge($this->queryBindings, $value);
    }

    /**
     * @param string $column
     * @param array $value
     * @param string $boolean
     * @param array $conditions
     * @return void
     */
    private function handleBetweenOperator(string $column, array $value, string $boolean, array &$conditions): void
    {
        $conditions[] = "{$boolean}{$column} BETWEEN ? AND ?";
        $this->queryBindings[] = $value[0];
        $this->queryBindings[] = $value[1];
    }

    /**
     * @param string $operator
     * @param string $column
     * @param mixed $value
     * @param string $boolean
     * @param array $conditions
     * @return void
     */
    private function handleDefaultOperator(string $operator, string $column, mixed $value, string $boolean, array &$conditions): void
    {
        $conditions[] = "{$boolean}{$column} {$operator} ?";
        $this->queryBindings[] = $value;
    }
}
