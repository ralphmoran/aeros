<?php

namespace Aeros\Src\Classes;

/**
 * Base class for models.
 * 
 * @method static ?Model find(int|array $filter, ?array $columns = null) Finds only one record from current model.
 */
abstract class Model
{
    const SELECT = 1;
    const INSERT = 2;
    const UPDATE = 4;
    const DELETE = 8;

    /** @var int */
    protected $crudAction = Model::SELECT;

    /** @var ?string Defines the name of the table to be used for this model. */
    protected $table = null;

    /** @var ?string Primary key to look for as unique value from table. */
    protected $primary = 'id';

    /** @var array List of specific column names that can be filled on INSERTS and UPDATES. */
    protected $fillable = [];

    /** @var array List of specific column names excluded from INSERTS and UPDATES. */
    protected $guarded = [];

    /** @var array List of dynamic properties (columns). This array will be filled up with values from model table. */
    protected $properties = [];

    /** @var array Pending values for commit : UPDATE and INSERT. */
    protected $onCommit = [];

    /** @var array Pending WHERE clause for UPDATE and DELETE actions. */
    protected $where = [];

    /** @var bool */
    protected $instantiated = false;

    /**
     * Sets new values as pending on commit event for INSERT action.
     *
     * @param array $newValues
     * @return Model
     * @throws \InvalidArgumentException
     */
    private function create(array $newValues): mixed
    {
        if (! $this->instantiated) {
            $this->crudAction = Model::INSERT;

            $this->setPendingValuesForCommit($newValues);

            return $this->commit();
        }
    }

    /**
     * Inserts multiple records at once.
     *
     * @param array $records
     * @return Model
     * @throws \InvalidArgumentException
     */
    private function createMany(array $records): mixed
    {
        if (! $this->instantiated) {

            $lastInderIds = [];
            $keys = array_keys($records[0]);
            $cols = implode(',', $keys);

            $placeholders = rtrim(
                implode('', 
                    array_map(
                        fn ($k): string => ':' . $k . ', ', 
                        $keys
                    )
                ), 
                ', '
            );

            $stm = db()->prepare("INSERT INTO {$this->getTableNameFromModel()} ({$cols}) VALUES ({$placeholders})");

            db()->beginTransaction();
            
            foreach ($records as $record) {
                $stm->execute($record);
                $lastInderIds[] = $stm->lastInsertId();
            }

            db()->commit();

            $filters = array_map(
                fn ($id): array => ['id', '=', $id, 'OR'],
                $lastInderIds
            );

            return Model::find($filters);
        }
    }

    /**
     * Sets new values as pending on commit event for UPDATE action.
     *
     * @param array $updatedValues
     * @param array $where
     * @return Model
     * @throws \InvalidArgumentException
     */
    private function update(array $updatedValues = [], array $where = []): Model
    {
        $this->crudAction = Model::UPDATE;
        $this->setPendingValuesForCommit($updatedValues);

        // Update one or more records
        if (! $this->instantiated) {
            $this->setPropertiesFromModel();
            $this->where = $where ?: [];
        }

        return $this;
    }

    /**
     * Deletes current model from table.
     *
     * @param array $where
     * @return mixed
     */
    private function delete(array $where = []): mixed
    {
        $this->crudAction = Model::DELETE;

        // Delete one or more records by $where filter
        if (! $this->instantiated) {
            $this->where = $where ?: [];
        }

        return $this;
    }

    /**
     * Updates the current model.
     *
     * @return mixed
     */
    private function save(): mixed
    {
        if ($this->instantiated) {
            $this->crudAction = Model::UPDATE;

            return $this->commit();
        }
    }

    /**
     * Finds only one record from current model.
     *
     * @param array An array of associative arrays.
     * @param ?array An array of required columns. Query fully or partial hydrated.
     * @return mixed Model, null or array
     */
    private function find(int|array $filter, ?array $columns = null): mixed
    {
        // This method won't work on already instantiated models
        if ($this->instantiated) {
            throw new \BadMethodCallException(
                sprintf(
                    'ERROR[BadMethodCallException] Method "%s" is not allowed on independent objects of type %s', 
                    __METHOD__,
                    get_class($this)
                )
            );
        }

        $this->crudAction = Model::SELECT;
        $columns = implode(', ', $columns ?? ['*']);

        // Find the first record that matches ${model_name}.id = ${filter}
        if (is_int($filter)) {
            $primary = $this->primary ?? 'id';

            $found = db()->prepare("SELECT {$columns} FROM {$this->getTableNameFromModel()} WHERE {$primary} = ?")
                ->execute([$filter])
                ->fetch(\PDO::FETCH_ASSOC);

            // If found any record, the associative array will be processed as properties.
            if ($found) {
                return $this->transformRecordToModel($found, get_class($this));
            }

            return null;
        }

        // Find records that matches ${model_name}.${keys} = ${filters}
        if (is_array($filter)) {
            $placeholders = '';
            $boundValues = [];
            $operator = '';

            foreach ($filter as $keys) {

                // {key} {operator} {value} {logical operator}
                if (count($keys) == 4) {
                    $operator = $keys[3];
                    $placeholders .= "{$keys[0]} {$keys[1]} ? {$keys[3]} ";
                    $boundValues[] = $keys[2];
                }

                // {key} {operator} {value} AND
                if (count($keys) == 3) {
                    $placeholders .= "{$keys[0]} {$keys[1]} ? AND ";
                    $boundValues[] = $keys[2];
                }

                // {key} = {value} AND
                if (count($keys) == 2) {
                    $placeholders .= "{$keys[0]} = ? AND ";
                    $boundValues[] = $keys[1];
                }
            }

            $placeholders = rtrim($placeholders, " AND {$operator}");

            $founds = db()->prepare("SELECT {$columns} FROM {$this->getTableNameFromModel()} WHERE {$placeholders}")
                ->execute($boundValues)
                ->fetchAll(\PDO::FETCH_ASSOC);

            // All found records will be casted as the current model type and
            // stored and returned in an array.
            if ($founds) {

                if (count($founds) == 1) {
                    return $this->transformRecordToModel($founds[0], get_class($this));
                }

                foreach ($founds as $index => $record) {
                    $founds[$index] = $this->transformRecordToModel($record, get_class($this));
                }

                return $founds;
            }

            return null;
        }
    }

    /**
     * Commits pending Insert-Update-Delete transactions.
     *
     * @return mixed
     */
    public function commit(): mixed
    {
        switch ($this->crudAction) {
            case Model::INSERT:

                if (! $this->instantiated) {

                    $placeholders = '';
                    $boundValues = [];

                    // Remove primary key, it's not required on INSERT
                    unset($this->onCommit[$this->primary]);

                    $cols = implode(', ', array_keys($this->onCommit));

                    $placeholders = rtrim(
                        implode('', 
                            array_map(
                                fn ($k): string => ':' . $k . ', ', 
                                array_keys($this->onCommit)
                            )
                        ), 
                        ', '
                    );

                    $lastInsertId = db()->prepare("INSERT INTO {$this->getTableNameFromModel()} ({$cols}) VALUES ({$placeholders})")
                        ->execute($this->onCommit);

                    // On success, bring all data from this new model
                    if ($lastInsertId) {
                        return Model::find(db()->lastInsertId());
                    }

                    return $lastInsertId;
                }

                break;

            case Model::UPDATE:

                // If there is nothing to commit, return null
                if (empty($this->onCommit)) {
                    return null;
                }

                $placeholders = '';
                $boundValues = [];

                // Independet object
                if ($this->instantiated && empty($this->where)) {
                    $placeholders = "{$this->primary} = :{$this->primary}";
                    $boundValues = $this->onCommit + [$this->primary => $this->properties[$this->primary]];

                    $cols = rtrim(
                        implode('', 
                            array_map(
                                fn ($colname): string => $colname . " = :{$colname}, ", 
                                array_keys($this->onCommit)
                            )
                        ), 
                        ', '
                    );
                }

                // General use
                if (! $this->instantiated && ! empty($this->where)) {
                    $this->parseWhere($this->where, $placeholders, $boundValues);
                    $boundValues = array_merge(array_values($this->onCommit), array_values($boundValues));

                    $cols = implode(' = ?, ', array_keys($this->onCommit)) . ' = ?';
                }

                $statement = db()
                    ->prepare("UPDATE {$this->getTableNameFromModel()} SET {$cols} WHERE {$placeholders}")
                    ->execute($boundValues);

                if ($statement) {

                    // Update properties
                    foreach ($this->onCommit as $property => $value) {
                        $this->properties[$property] = $value;
                    }

                    $this->onCommit = [];
                    $this->crudAction = Model::SELECT;
                }

                return $statement;

                break;

            case Model::DELETE:

                $placeholders = '';
                $boundValues = [];

                // Used by the Model instance, e.eg.: App\Models\User
                if ($this->instantiated && empty($this->where)) {
                    $placeholders = "{$this->primary} = :{$this->primary}";
                    $boundValues = [$this->primary => $this->properties[$this->primary]];
                }

                // It's used directly by Model children
                if (! $this->instantiated && ! empty($this->where)) {
                    $this->parseWhere($this->where, $placeholders, $boundValues);
                }

                return db()->prepare("DELETE FROM {$this->getTableNameFromModel()} WHERE {$placeholders}")
                    ->execute($boundValues);

                break;

            // Reset global flags
            $this->where = [];

            return false;
        }
    }

    /**
     * Sets the value of the property instantiated to $state.
     *
     * @param boolean $state
     * @return void
     */
    public function setIntantiation(bool $state)
    {
        $this->instantiated = $state;
    }

    /**
     * Stores pending values for further commit on CRUD actions.
     *
     * @param array $newValues
     * @return void
     */
    private function setPendingValuesForCommit(array $newValues)
    {
        if (in_array($this->primary, array_keys($newValues))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Primary key "%s" cannot be updated', 
                    $this->primary
                )
            );
        }

        // Only for model objects
        if ($this->instantiated) {

            // Not mapped properties
            $unknowns = implode(', ', array_values(
                array_diff(
                    array_keys($newValues), 
                    array_keys($this->properties)
                )
            ));

            if ($unknowns) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'ERROR[model.property] Properties "%s" are not mapped to any column', 
                        $unknowns
                    )
                );
            }
        }

        // Set values for commit
        foreach ($this->getFillableColumns() as $col) {
            if (isset($newValues[$col])) {
                $this->onCommit[$col] = $newValues[$col];

                continue;
            }
        }
    }

    /**
     * Brings all columns from current model/table and set them as properties.
     *
     * @return void
     */
    private function setPropertiesFromModel() 
    {
        $stm = db()->query("SELECT * FROM {$this->getTableNameFromModel()}");

        foreach (range(0, $stm->columnCount() -1) as $colIndex) {
            $this->properties[$stm->getColumnMeta($colIndex)['name']] = null;
        }
    }

    /**
     * Parses the $where clause and sets new values for the placeholders and bound values.
     *
     * @param array $where
     * @param string $placeholders
     * @param array $boundValues
     * @return void
     * @throws InvalidArgumentException
     */
    private function parseWhere(array $where, string &$placeholders, array &$boundValues)
    {
        $operator = '';

        # TODO: Add logic for 'IN' operator. Multiple values as an array
        # TODO: Add logic for these operators: https://chat.openai.com/c/b14d635f-85e7-402c-bf80-9f2262d1a373
        foreach ($where as $keys) {

            // Bad format for constrain
            if (count($keys) > 4) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'ERROR[model.property] Too many arguments. 3 or 2 areguments are required for column "%s"', 
                        $keys[0]
                    )
                );
            }

            // {key} {operator} {value} {logical operator}
            if (count($keys) == 4) {
                $operator = $keys[3];
                $placeholders .= "{$keys[0]} {$keys[1]} ? {$keys[3]} ";
                $boundValues[] = $keys[2];

                continue;
            }

            // {key} {operator} {value} AND
            if (count($keys) == 3) {
                $placeholders .= "{$keys[0]} {$keys[1]} ? AND ";
                $boundValues[] = $keys[2];

                continue;
            }

            // {key} = {value} AND
            if (count($keys) == 2) {
                $placeholders .= "{$keys[0]} = ? AND ";
                $boundValues[] = $keys[1];

                continue;
            }

            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Missing value for column "%s"', 
                    $keys[0]
                )
            );
        }

        $placeholders = rtrim($placeholders, " AND {$operator}");
    }

    /**
     * Transforms a record into a new model.
     *
     * @param array $record
     * @param string $class
     * @return Model
     */
    private function transformRecordToModel(array $record, string $class): Model
    {
        if (! class_exists($class)) {
            throw new \Exception(
                sprintf('ERROR[Class] Class "%s" does not exist', $class)
            );
        }

        $newInstance = new $class;

        foreach ($record as $col => $value) {
            $newInstance->$col = $value;
        }

        $newInstance->setIntantiation(true);

        return $newInstance;
    }

    /**
     * Returns the DB table linked to current model.
     *
     * @return ?string
     */
    private function getTableNameFromModel(): ?string
    {
        if (! is_null($this->table)) {
            return $this->table;
        }

        $modelName = get_class($this);

        if (str_contains($modelName, "\\")) {
            return $this->table = pluralize(strtolower(substr(strrchr($modelName, "\\"), 1)));
        }

        return null;
    }

    /**
     * Prepares a query string from an array of keys. 
     * It concatenates a logic operator.
     *
     * @param array $keys
     * @param string $operator
     * @return string
     */
    private function prepareQueryFromKeys(array $keys, string $operator = 'AND'): string
    {
        return implode(" = ? {$operator} ", $keys) . " = ?";
    }

    /**
     * Get the final columns to fill up.
     *
     * @return array
     */
    private function getFillableColumns(): array
    {
        $tmpfillable = $this->fillable ?: array_keys($this->properties);

        return array_values(array_diff($tmpfillable, [$this->primary]));
    }

    /**
     * Returns the primary key that model uses.
     *
     * @return void
     */
    public function getPrimaryKey()
    {
        return $this->primary;
    }

    /**
     * Sets properties dynamically.
     *
     * @param string $property
     * @param mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function __set($property, $value)
    {
        // Guarded properties
        if ($this->instantiated && in_array($property, array_diff($this->guarded, $this->fillable))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Property "%s" is guarded from any update', 
                    $property
                )
            );
        }

        // Validate primary key
        if ($this->instantiated && $property == $this->primary) {
            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Primary key "%s" cannot be updated', 
                    $property
                )
            );
        }

        // Chek if requested property exists
        if ($this->instantiated && ! in_array($property, array_keys($this->properties))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Property "%s" is not mapped to any column on "%s" table', 
                    $property,
                    $this->getTableNameFromModel()
                )
            );
        }

        // First time asigning property values
        if (! $this->instantiated) {
            $this->properties[$property] = $value;
        }

        // After instatiation, store this value for commit if requested 
        if ($this->instantiated) {
            $this->onCommit[$property] = $value;
            $this->crudAction = Model::UPDATE;
        }
    }

    /**
     * Returns the value of the property.
     *
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __get(string $property): mixed
    {
        if (! isset($this->$property) && ! in_array($property, array_keys($this->properties))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Property "%s" is not mapped to any column on "%s" table', 
                    $property,
                    $this->getTableNameFromModel()
                )
            );
        }

        // Check if the property was previously updated, if so, return it
        if (in_array($property, array_keys($this->onCommit))) {
            return $this->onCommit[$property];
        }

        if (isset($this->$property) && ! in_array($property, array_keys($this->properties))) {
            $this->properties[$property] = $this->$property;
        }

        return $this->properties[$property];
    }

    /**
     * Makes the calls to a private|protected methods with arguments.
     *
     * @param string $method
     * @param mixed $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, $arguments)
    {
        $class = get_called_class();

        if (! in_array($method, get_class_methods($class))) {
            throw new \BadMethodCallException(
                sprintf(
                    'ERROR[method] Method "%s" does not exist in model "%s"', 
                    $method,
                    $class
                )
            );
        }

        return (new $class())->$method(...$arguments);
    }

    /**
     * Calls unreachable methods.
     *
     * @param string $method
     * @param mixed $arguments
     * @return void
     */
    public function __call(string $method, $arguments)
    {
        if (! in_array($method, get_class_methods(get_called_class()))) {
            throw new \BadMethodCallException(
                sprintf(
                    'ERROR[method] Method "%s" does not exist in model "%s"', 
                    $method,
                    get_called_class()
                )
            );
        }

        return call_user_func_array([$this, $method], $arguments);
    }

    /**
     * Define a "has one" relationship.
     * 
     * If found, it will return the last record inserted from $relatedModel.
     *
     * @param   string  $relatedModel   The related model class name
     * @return  mixed   The related model instance
     */
    private function hasOne(string $relatedModel)
    {
        return $this->getHasRelationship($relatedModel);
    }

    /**
     * Define a "has many" relationship.
     * 
     * If found, it will return all the records inserted from $relatedModel.
     *
     * @param   string  $relatedModel   The related model class name
     * @return  mixed   The related model instances
     */
    private function hasMany(string $relatedModel)
    {
        return $this->getHasRelationship($relatedModel, true);
    }

    /**
     * Retrieve related model(s) based on the relationship type.
     *
     * @param   string  $relatedModel   The name of the related model.
     * @param   bool    $hasMany        Indicates whether the relationship is hasMany (default: false).
     *
     * @return  mixed   Returns the related model or models based on the relationship type.
     */
    private function getHasRelationship(string $relatedModel, bool $hasMany = false)
    {
        $calledModel = get_called_class();

        $pivotTableScheme = self::getPivotTableScheme(
            $calledModel, 
            $relatedModel
        );

        $relatedModel_primary = (new $relatedModel)->getPrimaryKey();
        $relatedModel_column = strtolower(class_basename($relatedModel)) . '_' . $relatedModel_primary;

        $has = db()
            ->prepare(
                "SELECT {$relatedModel_column} FROM {$pivotTableScheme['name']} WHERE " 
                . strtolower(class_basename($calledModel)) . '_' . $this->primary 
                . " = ? ORDER BY id DESC " 
                . ((! $hasMany) ? "LIMIT 1" : "")
            )
            ->execute([$this->id]);

        // hasOne relationship
        if (! $hasMany) {
            $has = $has->fetch();

            return (new $relatedModel)->find($has[$relatedModel_column]);
        }

        // hasMany relationship
        if ($hasMany) {

            foreach($has->fetchAll() as $key => $value) {
                $filters[] = [$relatedModel_primary, '=', $value[$relatedModel_column], 'OR'];
            }

            return (new $relatedModel)->find($filters);
        }

        return null;
    }

    /**
     * Get the pivot table scheme from two given models.
     *
     * @param   string  $calledModel    The class name of the called model.
     * @param   string  $relatedModel   The class name of the related model.
     *
     * @return  array|false     An array containing the pivot table scheme with the following keys:
     *                     - 'name': The name of the pivot table.
     *                     - 'col1': The column name for the first model's primary key in the pivot table.
     *                     - 'col2': The column name for the second model's primary key in the pivot table.
     *                     Returns false if the comparison between models fails.
     */
    public static function getPivotTableScheme(string $calledModel, string $relatedModel) 
    {
        $calledModelClass = new $calledModel();
        $relatedModelClass = new $relatedModel();

        $relatedModel = strtolower(
            class_basename($relatedModel)
        );

        $calledModel = strtolower(
            class_basename($calledModel)
        );

        $result = strcmp($calledModel, $relatedModel);

        if ($result < 0) {
            return [ 
                'name' => $calledModel . '_' . $relatedModel,
                'col1' => $calledModel . '_' . $calledModelClass->getPrimaryKey(),
                'col2' => $relatedModel . '_' . $relatedModelClass->getPrimaryKey(),
            ];
        }

        if ($result > 0) {
            return [ 
                'name' => $relatedModel . '_' . $calledModel,
                'col1' => $relatedModel . '_' . $relatedModelClass->getPrimaryKey(),
                'col2' => $calledModel . '_' . $calledModelClass->getPrimaryKey(),
            ];
        }

        return false;
    }
}
