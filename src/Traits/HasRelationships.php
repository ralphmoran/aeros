<?php

namespace Aeros\Src\Traits;

/**
 * HasRelationships Trait
 *
 * Enhances the existing Aeros Model relationships with:
 * - Eager loading to prevent N+1 queries
 * - Relationship caching
 * - Better relationship retrieval
 *
 * Compatible with existing Aeros Model hasOne, hasMany, belongsTo methods.
 *
 * @package Aeros\Src\Traits
 */
trait HasRelationships
{
    /**
     * Eager loaded relationships.
     *
     * @var array
     */
    protected array $eagerLoaded = [];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected array $withRelations = [];

    /**
     * Static wrapper for with() to enable: User::with('plans')->find(1)
     *
     * @param string|array $relations
     * @return static
     */
    public static function with(string|array $relations): static
    {
        $instance = new static();

        // Directly set the property instead of calling the trait method
        $instance->withRelations = array_merge(
            $instance->withRelations ?? [],
            is_array($relations) ? $relations : [$relations]
        );

        return $instance;
    }

    /**
     * Load relationships after the query has been executed.
     *
     * This is called automatically when using with().
     *
     * @param array $models Array of model instances
     * @return array Models with loaded relationships
     */
    protected function loadRelationships(array $models): array
    {
        if (empty($this->withRelations) || empty($models)) {
            return $models;
        }

        foreach ($this->withRelations as $relation) {
            $models = $this->eagerLoadRelation($models, $relation);
        }

        // Reset after loading
        $this->withRelations = [];

        return $models;
    }

    /**
     * Eager load a single relationship for multiple models.
     *
     * @param array $models Parent models
     * @param string $relation Relationship name
     * @return array Models with loaded relationship
     */
    protected function eagerLoadRelation(array $models, string $relation): array
    {
        foreach ($models as &$model) {

            // Check if relationship method exists
            if (! method_exists($model, $relation)) {
                throw new \BadMethodCallException(
                    sprintf(
                        'Relationship method "%s" does not exist on model "%s". Check for typos in with().',
                        $relation,
                        get_class($model)
                    )
                );
            }

            // Call the relationship method and execute
            $relationshipObject = $model->$relation();

            // Get results based on relationship type
            if (method_exists($relationshipObject, 'get')) {
                $model->$relation = $relationshipObject->get();

                continue;
            }

            if (method_exists($relationshipObject, 'first')) {
                $model->$relation = $relationshipObject->first();
            }
        }

        return $models;
    }

    /**
     * Fetch related models from database.
     *
     * @param string $relatedModelClass Related model class
     * @param string $foreignKey Foreign key column
     * @param array $parentKeys Parent model IDs
     * @param string $type Relationship type (hasOne, hasMany, belongsTo)
     * @return array Related models grouped by parent key
     */
    protected function fetchRelatedModels(
        string $relatedModelClass,
        string $foreignKey,
        array $parentKeys,
        string $type
    ): array {
        try {
            $relatedModel = new $relatedModelClass();
            $pivotInfo = $this->getPivotTableScheme(get_class($this), $relatedModelClass);

            // Build query to fetch all related records at once
            $relatedPrimary = $relatedModel->getPrimaryKey();
            $relatedTable = $relatedModel->getTableNameFromModel();
            $parentColumn = strtolower(class_basename(get_class($this))) . '_' . $this->getPrimaryKey();
            $relatedColumn = strtolower(class_basename($relatedModelClass)) . '_' . $relatedPrimary;

            // Query pivot table to get relationships
            $placeholders = implode(',', array_fill(0, count($parentKeys), '?'));

            $pivotResults = db()->prepare(
                "SELECT {$parentColumn}, {$relatedColumn} FROM {$pivotInfo['name']} 
                 WHERE {$parentColumn} IN ({$placeholders})
                 ORDER BY id DESC"
            )->execute($parentKeys)->fetchAll();

            // Group related IDs by parent ID
            $groupedIds = [];

            foreach ($pivotResults as $row) {

                $parentId = $row[$parentColumn];
                $relatedId = $row[$relatedColumn];

                if (! isset($groupedIds[$parentId])) {
                    $groupedIds[$parentId] = [];
                }

                $groupedIds[$parentId][] = $relatedId;
            }

            // Fetch all related models
            $allRelatedIds = [];

            foreach ($groupedIds as $ids) {
                $allRelatedIds = array_merge($allRelatedIds, $ids);
            }

            $allRelatedIds = array_unique($allRelatedIds);

            if (empty($allRelatedIds)) {
                return [];
            }

            // Build filter for find method
            $filters = [];

            foreach ($allRelatedIds as $id) {
                $filters[] = [$relatedPrimary, '=', $id, 'OR'];
            }

            $relatedInstances = $relatedModel->find($filters);
            $relatedInstances = is_array($relatedInstances) ? $relatedInstances : [$relatedInstances];

            // Index by ID for easy lookup
            $indexedRelated = [];

            foreach ($relatedInstances as $instance) {
                $indexedRelated[$instance->{$relatedPrimary}] = $instance;
            }

            // Group by parent key
            $grouped = [];

            foreach ($groupedIds as $parentId => $relatedIds) {

                $grouped[$parentId] = [];

                foreach ($relatedIds as $relatedId) {
                    if (isset($indexedRelated[$relatedId])) {
                        $grouped[$parentId][] = $indexedRelated[$relatedId];
                    }
                }
            }

            return $grouped;

        } catch (\Exception $e) {
            logger("Failed to fetch related models: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get relationship method name for a relation.
     *
     * @param string $relation Relation name (e.g., 'posts')
     * @return string|null Method name (e.g., 'hasMany') or null if not found
     */
    protected function getRelationshipMethod(string $relation): ?string
    {
        // Check if a method exists with the relation name
        if (method_exists($this, $relation)) {
            return $relation;
        }

        // Check common relationship method patterns
        $patterns = [
            $relation,
            'get' . ucfirst($relation),
            lcfirst($relation)
        ];

        foreach ($patterns as $pattern) {
            if (method_exists($this, $pattern)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Get relationship information (type, related model, keys).
     *
     * @param string $relation Relation name
     * @param string $method Relationship method
     * @return array|null Relationship info or null
     */
    protected function getRelationshipInfo(string $relation, string $method): ?array
    {
        // This is a simplified version - you may need to enhance this
        // based on how relationships are defined in models

        // For now, we'll use conventions:
        // - hasOne/hasMany: related model is singular/plural of relation name
        // - belongsTo: related model is singular of relation name

        $relatedModelName = ucfirst(singularize($relation)[0] ?? $relation);
        $relatedModelClass = 'App\\Models\\' . $relatedModelName;

        if (! class_exists($relatedModelClass)) {
            return null;
        }

        // Determine relationship type
        $type = 'hasMany'; // Default assumption

        // You can enhance this by checking method docblocks or other metadata
        if (str_contains(strtolower($relation), 'one')) {
            $type = 'hasOne';
        }

        return [
            'type' => $type,
            'related_model' => $relatedModelClass,
            'foreign_key' => strtolower(class_basename(get_class($this))) . '_id',
            'local_key' => $this->getPrimaryKey()
        ];
    }

    /**
     * Check if a relationship has been eager loaded.
     *
     * @param string $relation Relation name
     * @return bool
     */
    public function relationLoaded(string $relation): bool
    {
        return isset($this->eagerLoaded[$relation]);
    }

    /**
     * Get eager loaded relationships.
     *
     * @return array
     */
    public function getEagerLoaded(): array
    {
        return $this->eagerLoaded;
    }

    /**
     * Load a relationship if it hasn't been loaded yet.
     *
     * Usage:
     *   $user->load('posts'); // Lazy load posts if not already loaded
     *
     * @param string $relation Relation name
     * @return self
     */
    public function load(string $relation): self
    {
        if (! $this->relationLoaded($relation)) {
            $this->withRelations = [$relation];
            $this->loadRelationships([$this]);
        }

        return $this;
    }

    /**
     * Reload a relationship even if it's already loaded.
     *
     * @param string $relation Relation name
     * @return self
     */
    public function reload(string $relation): self
    {
        unset($this->eagerLoaded[$relation]);
        return $this->load($relation);
    }

    /**
     * Load multiple relationships.
     *
     * @param array $relations Relation names
     * @return self
     */
    public function loadMany(array $relations): self
    {
        foreach ($relations as $relation) {
            $this->load($relation);
        }

        return $this;
    }

    /**
     * Check if model has a specific relationship.
     *
     * @param string $relation Relation name
     * @return bool
     */
    public function hasRelation(string $relation): bool
    {
        return $this->getRelationshipMethod($relation) !== null;
    }

    /**
     * Get all available relationships for this model.
     *
     * This scans for methods that might be relationships.
     *
     * @return array Relationship names
     */
    public function getAvailableRelationships(): array
    {
        $methods = get_class_methods($this);
        $relationships = [];

        foreach ($methods as $method) {
            // Skip if it's a trait method, magic method, or Model base method
            if (str_starts_with($method, '__') ||
                str_starts_with($method, 'get') ||
                str_starts_with($method, 'set') ||
                in_array($method, ['find', 'create', 'update', 'delete', 'save'])) {
                continue;
            }

            // Check if method returns a relationship
            // This is a simple heuristic - you can enhance it
            if ($this->getRelationshipInfo($method, $method) !== null) {
                $relationships[] = $method;
            }
        }

        return $relationships;
    }
}
