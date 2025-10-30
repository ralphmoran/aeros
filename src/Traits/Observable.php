<?php

namespace Aeros\Src\Traits;

/**
 * Observable Trait
 *
 * Provides model observer functionality with hooks/events for CRUD operations:
 * - beforeCreate / afterCreate
 * - beforeUpdate / afterUpdate
 * - beforeSave / afterSave (fires on both create and update)
 * - beforeDelete / afterDelete
 * - beforeFind / afterFind
 *
 * Usage in Model:
 *   protected function beforeCreate(array &$data): void {
 *       $data['created_by'] = auth()->id();
 *   }
 *
 * @package Aeros\Src\Traits
 */
trait Observable
{
    /**
     * Global observers registered for all models.
     *
     * @var array
     */
    protected static array $globalObservers = [];

    /**
     * Model-specific observers.
     *
     * @var array
     */
    protected array $observers = [];

    /**
     * Events that have been fired for this instance.
     *
     * @var array
     */
    protected array $firedEvents = [];

    /**
     * Register a global observer for all models.
     *
     * Usage:
     *   Model::observe(new AuditLogObserver());
     *
     * @param object $observer Observer instance with hook methods
     * @return void
     */
    public static function observe(object $observer): void
    {
        $className = get_called_class();

        if (!isset(self::$globalObservers[$className])) {
            self::$globalObservers[$className] = [];
        }

        self::$globalObservers[$className][] = $observer;
    }

    /**
     * Register a model-specific observer.
     *
     * @param object $observer Observer instance
     * @return void
     */
    public function observeInstance(object $observer): void
    {
        $this->observers[] = $observer;
    }

    /**
     * Fire a model event/hook.
     *
     * Calls methods in this order:
     * 1. Model's own method (e.g., $this->beforeCreate())
     * 2. Instance observers
     * 3. Global observers
     *
     * @param string $event Event name (e.g., 'beforeCreate')
     * @param mixed ...$args Arguments to pass to hooks
     * @return bool True if event should proceed, false if cancelled
     */
    protected function fireModelEvent(string $event, mixed ...$args): bool
    {
        // Track fired events
        $this->firedEvents[] = $event;

        // Call model's own method if exists
        if (method_exists($this, $event)) {
            $result = $this->$event(...$args);

            // If method returns false, cancel the operation
            if ($result === false) {
                $this->logError("Operation cancelled by {$event} hook");
                return false;
            }
        }

        // Call instance observers
        foreach ($this->observers as $observer) {
            if (method_exists($observer, $event)) {
                $result = $observer->$event($this, ...$args);

                if ($result === false) {
                    return false;
                }
            }
        }

        // Call global observers
        $className = get_called_class();

        if (isset(self::$globalObservers[$className])) {
            foreach (self::$globalObservers[$className] as $observer) {
                if (method_exists($observer, $event)) {
                    $result = $observer->$event($this, ...$args);

                    if ($result === false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Fire 'creating' event before a record is created.
     *
     * Hooks can modify $data by reference.
     *
     * @param array &$data Data to be inserted
     * @return bool
     */
    protected function fireCreatingEvent(array &$data): bool
    {
        return $this->fireModelEvent('beforeCreate', $data)
            && $this->fireModelEvent('beforeSave', $data);
    }

    /**
     * Fire 'created' event after a record is created.
     *
     * @param mixed $model The created model instance or ID
     * @return bool
     */
    protected function fireCreatedEvent(mixed $model): bool
    {
        return $this->fireModelEvent('afterCreate', $model)
            && $this->fireModelEvent('afterSave', $model);
    }

    /**
     * Fire 'updating' event before a record is updated.
     *
     * @param int $id Record ID
     * @param array &$data Data to be updated
     * @return bool
     */
    protected function fireUpdatingEvent(int $id, array &$data): bool
    {
        return $this->fireModelEvent('beforeUpdate', $id, $data)
            && $this->fireModelEvent('beforeSave', $data);
    }

    /**
     * Fire 'updated' event after a record is updated.
     *
     * @param int $id Record ID
     * @param array $data Updated data
     * @return bool
     */
    protected function fireUpdatedEvent(int $id, array $data): bool
    {
        return $this->fireModelEvent('afterUpdate', $id, $data)
            && $this->fireModelEvent('afterSave', $data);
    }

    /**
     * Fire 'deleting' event before a record is deleted.
     *
     * @param int $id Record ID
     * @return bool
     */
    protected function fireDeletingEvent(int $id): bool
    {
        return $this->fireModelEvent('beforeDelete', $id);
    }

    /**
     * Fire 'deleted' event after a record is deleted.
     *
     * @param int $id Record ID
     * @return bool
     */
    protected function fireDeletedEvent(int $id): bool
    {
        return $this->fireModelEvent('afterDelete', $id);
    }

    /**
     * Fire 'finding' event before finding a record.
     *
     * @param mixed $filter Find filter
     * @return bool
     */
    protected function fireFindingEvent(mixed $filter): bool
    {
        return $this->fireModelEvent('beforeFind', $filter);
    }

    /**
     * Fire 'found' event after finding a record.
     *
     * @param mixed $result Found record(s)
     * @return bool
     */
    protected function fireFoundEvent(mixed $result): bool
    {
        return $this->fireModelEvent('afterFind', $result);
    }

    /**
     * Get events that have been fired for this instance.
     *
     * @return array
     */
    public function getFiredEvents(): array
    {
        return $this->firedEvents;
    }

    /**
     * Check if a specific event has been fired.
     *
     * @param string $event Event name
     * @return bool
     */
    public function hasEventFired(string $event): bool
    {
        return in_array($event, $this->firedEvents);
    }

    /**
     * Clear fired events history.
     *
     * @return void
     */
    public function clearFiredEvents(): void
    {
        $this->firedEvents = [];
    }

    /**
     * Disable observers temporarily.
     *
     * Usage:
     *   $user->withoutObservers(function() use ($user) {
     *       $user->update(['status' => 'banned']); // Won't fire hooks
     *   });
     *
     * @param \Closure $callback
     * @return mixed
     */
    public function withoutObservers(\Closure $callback): mixed
    {
        $originalObservers = $this->observers;
        $originalGlobalObservers = self::$globalObservers;

        $this->observers = [];
        self::$globalObservers = [];

        try {
            return $callback($this);
        } finally {
            $this->observers = $originalObservers;
            self::$globalObservers = $originalGlobalObservers;
        }
    }

    // ============================================
    // Default Hook Methods (can be overridden in models)
    // ============================================

    /**
     * Hook: Before creating a record.
     * Override this in your model to add custom logic.
     *
     * @param array &$data Data to be inserted (can be modified)
     * @return bool|void Return false to cancel creation
     */
    // protected function beforeCreate(array &$data): bool|void {}

    /**
     * Hook: After creating a record.
     *
     * @param mixed $model The created model
     * @return void
     */
    // protected function afterCreate(mixed $model): void {}

    /**
     * Hook: Before updating a record.
     *
     * @param int $id Record ID
     * @param array &$data Data to be updated (can be modified)
     * @return bool|void Return false to cancel update
     */
    // protected function beforeUpdate(int $id, array &$data): bool|void {}

    /**
     * Hook: After updating a record.
     *
     * @param int $id Record ID
     * @param array $data Updated data
     * @return void
     */
    // protected function afterUpdate(int $id, array $data): void {}

    /**
     * Hook: Before saving (create or update).
     *
     * @param array &$data Data to be saved
     * @return bool|void Return false to cancel save
     */
    // protected function beforeSave(array &$data): bool|void {}

    /**
     * Hook: After saving (create or update).
     *
     * @param mixed $model The saved model
     * @return void
     */
    // protected function afterSave(mixed $model): void {}

    /**
     * Hook: Before deleting a record.
     *
     * @param int $id Record ID
     * @return bool|void Return false to cancel deletion
     */
    // protected function beforeDelete(int $id): bool|void {}

    /**
     * Hook: After deleting a record.
     *
     * @param int $id Record ID
     * @return void
     */
    // protected function afterDelete(int $id): void {}

    /**
     * Hook: Before finding a record.
     *
     * @param mixed $filter Find filter
     * @return bool|void Return false to cancel find
     */
    // protected function beforeFind(mixed $filter): bool|void {}

    /**
     * Hook: After finding a record.
     *
     * @param mixed $result Found record(s)
     * @return void
     */
    // protected function afterFind(mixed $result): void {}
}
