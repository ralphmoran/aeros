<?php

namespace Aeros\Src\Classes;

use Aeros\Src\Classes\Relationships\HasOne;
use Aeros\Src\Classes\Relationships\HasMany;
use Aeros\Src\Classes\Relationships\BelongsTo;
use Aeros\Src\Classes\Relationships\BelongsToMany;
use Aeros\Src\Classes\Relationships\HasManyThrough;
use Aeros\Src\Traits\HasRelationships;

/**
 * Base class for models
 *
 * @method static ?Model find(int|array $filter, ?array $columns = null) Finds only one record from current model.
 */
abstract class Model
{
    use HasRelationships;

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
     * @return mixed
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
                $model = $this->transformRecordToModel($found, get_class($this));

                // Load eager relationships if any
                if (!empty($this->withRelations)) {
                    $models = $this->loadRelationships([$model]);
                    return $models[0] ?? $model;
                }

                return $model;
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

                // {key} = {value} AND (format: ['column' => 'value'])
                if (count($keys) == 1) {
                    $placeholders .= implode(array_keys($keys)) . " = ? AND ";
                    $boundValues[] = implode(array_values($keys));
                }
            }

            $placeholders = rtrim($placeholders, " AND {$operator}");

            $founds = db()->prepare("SELECT {$columns} FROM {$this->getTableNameFromModel()} WHERE {$placeholders}")
                ->execute($boundValues)
                ->fetchAll(\PDO::FETCH_ASSOC);

            // All found records will be casted as the current model type and
            // stored and returned in an array.
            if ($founds) {

                $models = [];
                foreach ($founds as $record) {
                    $models[] = $this->transformRecordToModel($record, get_class($this));
                }

                // Load eager relationships if any
                if (!empty($this->withRelations)) {
                    $models = $this->loadRelationships($models);
                }

                if (count($models) == 1) {
                    return $models[0];
                }

                return $models;
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
     * Define a "has one" relationship using direct foreign key.
     *
     * Returns a HasOne relationship instance for method chaining.
     *
     * Example:
     *   class User extends Model {
     *       public function clientSettings() {
     *           return $this->hasOne(ClientSettings::class);
     *       }
     *   }
     *
     *   Usage:
     *   $settings = $user->clientSettings()->first();
     *   $settings = $user->clientSettings()->where('active', true)->first();
     *
     * @param string $relatedModel The related model class name
     * @param string|null $foreignKey Foreign key on related table (auto-detected if null)
     * @param string|null $localKey Local key on parent table (auto-detected if null)
     * @param string|null $pivotTable Local key on parent table (auto-detected if null)
     * @return HasOne
     */
    protected function hasOne(
        string $relatedModel,
        ?string $foreignKey = null,
        ?string $localKey = null,
        ?string $pivotTable = null
    ): HasOne {
        $related = new $relatedModel();

        $foreignKey = $foreignKey ?? (strtolower(class_basename(get_class($this))) . '_' . $this->getPrimaryKey());
        $localKey = $localKey ?? $this->getPrimaryKey();

        return new HasOne($this, $related, $foreignKey, $localKey, $pivotTable);
    }

    /**
     * Define a "has many" relationship using direct foreign key.
     *
     * Returns a HasMany relationship instance for method chaining.
     *
     * Example:
     *   class User extends Model {
     *       public function plans() {
     *           return $this->hasMany(Plan::class);
     *       }
     *   }
     *
     *   Usage:
     *   $plans = $user->plans()->get();
     *   $activePlans = $user->plans()->where('status', 'active')->get();
     *
     * @param string $relatedModel The related model class name
     * @param string|null $foreignKey Foreign key on related table (auto-detected if null)
     * @param string|null $localKey Local key on parent table (auto-detected if null)
     * @param string|null $pivotTable Local key on parent table (auto-detected if null)
     * @return HasMany
     */
    protected function hasMany(
        string $relatedModel,
        ?string $foreignKey = null,
        ?string $localKey = null,
        ?string $pivotTable = null
    ): HasMany {
        $related = new $relatedModel();

        $foreignKey = $foreignKey ?? (strtolower(class_basename(get_class($this))) . '_' . $this->getPrimaryKey());
        $localKey = $localKey ?? $this->getPrimaryKey();

        return new HasMany($this, $related, $foreignKey, $localKey, $pivotTable);
    }

    /**
     * Define a "belongs to" (inverse) relationship using direct foreign key.
     *
     * Returns a BelongsTo relationship instance for method chaining.
     *
     * Example:
     *   class Plan extends Model {
     *       public function user() {
     *           return $this->belongsTo(User::class);
     *       }
     *   }
     *
     *   Usage:
     *   $user = $plan->user()->first();
     *
     * @param string $relatedModel The related model class name
     * @param string|null $foreignKey Foreign key on parent table (auto-detected if null)
     * @param string|null $ownerKey Owner key on related table (auto-detected if null)
     * @param string|null $pivotTable Owner key on related table (auto-detected if null)
     * @return BelongsTo
     */
    protected function belongsTo(
        string $relatedModel,
        ?string $foreignKey = null,
        ?string $ownerKey = null,
        ?string $pivotTable = null  // ← ADD THIS
    ): BelongsTo {
        $related = new $relatedModel();

        $foreignKey = $foreignKey ?? (strtolower(class_basename($relatedModel)) . '_' . $related->getPrimaryKey());
        $ownerKey = $ownerKey ?? $related->getPrimaryKey();

        return new BelongsTo($this, $related, $foreignKey, $ownerKey, $pivotTable);
    }

    /**
     * Define a "belongs to many" relationship using a pivot table.
     *
     * Returns a BelongsToMany relationship instance for method chaining.
     *
     * Example:
     *   class User extends Model {
     *       public function roles() {
     *           return $this->belongsToMany(Role::class);
     *       }
     *   }
     *
     *   Usage:
     *   $roles = $user->roles()->get();
     *   $activeRoles = $user->roles()->where('active', true)->get();
     *   $user->roles()->attach([1, 2, 3]);
     *   $user->roles()->detach([2]);
     *   $user->roles()->sync([1, 3, 4]);
     *
     * @param string $relatedModel The related model class name
     * @param string|null $pivotTable Pivot table name (auto-detected if null)
     * @param string|null $foreignPivotKey Foreign pivot key (auto-detected if null)
     * @param string|null $relatedPivotKey Related pivot key (auto-detected if null)
     * @param string|null $localKey Local key on parent table (auto-detected if null)
     * @param string|null $relatedKey Related key on related table (auto-detected if null)
     * @return BelongsToMany
     */
    protected function belongsToMany(
        string $relatedModel,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $localKey = null,
        ?string $relatedKey = null
    ): BelongsToMany {
        $related = new $relatedModel();

        return new BelongsToMany(
            $this,
            $related,
            $pivotTable,
            $foreignPivotKey,
            $relatedPivotKey,
            $localKey,
            $relatedKey
        );
    }

    /**
     * Define a "has many through" relationship through an intermediate model.
     *
     * Returns a HasManyThrough relationship instance for method chaining.
     *
     * Example:
     *   class Country extends Model {
     *       public function posts() {
     *           return $this->hasManyThrough(Post::class, User::class);
     *       }
     *   }
     *
     *   Usage:
     *   $posts = $country->posts()->get();
     *
     * @param string $relatedModel The related model class name
     * @param string $throughModel The intermediate model class name
     * @param string|null $firstKey First foreign key (auto-detected if null)
     * @param string|null $secondKey Second foreign key (auto-detected if null)
     * @param string|null $localKey Local key on parent table (auto-detected if null)
     * @param string|null $secondLocalKey Local key on intermediate table (auto-detected if null)
     * @return HasManyThrough
     */
    protected function hasManyThrough(
        string $relatedModel,
        string $throughModel,
        ?string $firstKey = null,
        ?string $secondKey = null,
        ?string $localKey = null,
        ?string $secondLocalKey = null
    ): HasManyThrough {
        $related = new $relatedModel();
        $through = new $throughModel();

        return new HasManyThrough(
            $this,
            $related,
            $through,
            $firstKey,
            $secondKey,
            $localKey,
            $secondLocalKey
        );
    }

    // ===================================================================
    // DEPRECATED OLD RELATIONSHIP METHODS (Kept for backwards compatibility)
    // ===================================================================

    /**
     * @deprecated Use new hasOne() method that returns Relationship objects
     */
    private function hasOne_old(string $relatedModel)
    {
        return $this->getHasRelationship($relatedModel);
    }

    /**
     * @deprecated Use new hasMany() method that returns Relationship objects
     */
    private function hasMany_old(string $relatedModel)
    {
        return $this->getHasRelationship($relatedModel, true);
    }

    /**
     * @deprecated This method will be removed. Use attach/detach on belongsToMany relationships.
     */
    private function has(array|Model $model): bool|Model
    {
        if ($this->instantiated) {

            if (is_array($model)) {

                foreach ($model as $singleModel) {

                    if (! ($singleModel instanceof Model)) {
                        throw new \TypeError(
                            sprintf(
                                'ERROR[TypeError] Model "%s" is not an instance of Aeros/Src/Model class.',
                                $singleModel
                            )
                        );
                    }

                    $this->createHasRelationship($singleModel);
                }

                return $this;
            }

            return $this->createHasRelationship($model);
        }
    }

    /**
     * @deprecated Use new belongsTo() method that returns Relationship objects
     */
    private function belongsTo_old(array|Model $model): bool|Model
    {
        return $this->has($model);
    }

    /**
     * @deprecated
     */
    private function createHasRelationship(Model $model): bool|Model
    {
        if ($this->instantiated) {

            $pivot = $this->getPivotTableScheme(get_called_class(), get_class($model));

            $data = [
                $pivot['col1'] => $model->id,
                $pivot['col2'] => $model->id,
            ];

            // Replace value for calledModel
            $data[
            strtolower(
                class_basename(get_called_class()) . '_' . $this->getPrimaryKey()
            )
            ] = $this->{$this->getPrimaryKey()};

            $stm = db()->prepare(
                'INSERT IGNORE INTO ' . $pivot['name'] . ' (' . $pivot['col1'] . ', ' . $pivot['col2'] .
                ') VALUES(:' . $pivot['col1']. ', :' . $pivot['col2'] . ')'
            )
                ->execute($data);

            // In case of error
            if (! $stm) {
                throw new \PDOException(
                    sprintf(
                        'ERROR[Query] It was not possible to create a new relationship for the "%s" table.',
                        $pivot['name']
                    )
                );
            }

            return $this;
        }

        return false;
    }

    /**
     * @deprecated
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
     * Transforms a record into a new model (NOW PUBLIC for Relationship classes).
     *
     * @param array $record
     * @param string $class
     * @return Model
     */
    public function transformRecordToModel(array $record, string $class): Model
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
     * Returns the DB table linked to current model (NOW PUBLIC for Relationship classes).
     *
     * @return ?string
     */
    public function getTableNameFromModel(): ?string
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
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primary;
    }

    /**
     * Clear cached relationship data.
     *
     * @param string $relationName
     * @return void
     */
    public function clearRelationCache(string $relationName): void
    {
        if (isset($this->properties[$relationName])) {
            unset($this->properties[$relationName]);
        }
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
        // Allow setting relationships (don't validate as columns)
        if (method_exists($this, $property)) {
            $this->properties[$property] = $value;
            return;
        }

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

        // Check if requested property exists
        if ($this->instantiated && ! in_array($property, array_keys($this->properties)) && !method_exists($this, $property)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Property "%s" is not mapped to any column on "%s" table',
                    $property,
                    $this->getTableNameFromModel()
                )
            );
        }

        // First time assigning property values
        if (! $this->instantiated) {
            $this->properties[$property] = $value;
        }

        // After instantiation, store this value for commit if requested
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
        // Check if property exists in properties first (eager loaded data)
        if (array_key_exists($property, $this->properties)) {
            return $this->properties[$property];
        }

        // Check if the property was previously updated
        if (in_array($property, array_keys($this->onCommit))) {
            return $this->onCommit[$property];
        }

        // Check if it's a relationship method (lazy load)
        if (method_exists($this, $property)) {
            $relation = $this->$property();

            if (is_object($relation) && method_exists($relation, 'get')) {
                return $relation->get();
            }
            if (is_object($relation) && method_exists($relation, 'first')) {
                return $relation->first();
            }

            return $relation;
        }

        if (! isset($this->$property) && ! in_array($property, array_keys($this->properties))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'ERROR[model.property] Property "%s" is not mapped to any column on "%s" table',
                    $property,
                    $this->getTableNameFromModel()
                )
            );
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
     * Get the pivot table scheme from two given models.
     *
     * @param string $calledModel The class name of the called model.
     * @param string $relatedModel The class name of the related model.
     * @return array|false
     */
    public static function getPivotTableScheme(string $calledModel, string $relatedModel)
    {
        $calledModelClass = new $calledModel();
        $relatedModelClass = new $relatedModel();

        // Use class basename and ensure singular form
        $relatedModelName = strtolower(class_basename($relatedModel));
        $calledModelName = strtolower(class_basename($calledModel));

        // Ensure singular (remove trailing 's' if exists for common cases)
        $relatedModelName = rtrim($relatedModelName, 's');
        $calledModelName = rtrim($calledModelName, 's');

        $result = strcmp($calledModelName, $relatedModelName);

        if ($result < 0) {
            return [
                'name' => $calledModelName . '_' . $relatedModelName,
                'col1' => $calledModelName . '_' . $calledModelClass->getPrimaryKey(),
                'col2' => $relatedModelName . '_' . $relatedModelClass->getPrimaryKey(),
            ];
        }

        if ($result > 0) {
            return [
                'name' => $relatedModelName . '_' . $calledModelName,
                'col1' => $relatedModelName . '_' . $relatedModelClass->getPrimaryKey(),
                'col2' => $calledModelName . '_' . $calledModelClass->getPrimaryKey(),
            ];
        }

        return false;
    }
}
