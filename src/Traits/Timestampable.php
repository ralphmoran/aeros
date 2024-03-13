<?php

namespace Aeros\Src\Traits;

/**
 * Trait Timestampable
 *
 * This trait provides functionality to manage timestamps for creation, updating, and soft deletion.
 *
 * @package Aeros\Src\Traits
 */
trait Timestampable
{
    /**
     * @var int|null The timestamp of when the record was created.
     */
    protected $created_at;

    /**
     * @var int|null The timestamp of when the record was last updated.
     */
    protected $updated_at;

    /**
     * @var int|null The timestamp of when the record was soft deleted.
     */
    protected $deleted_at;

    /**
     * Update the "updated_at" timestamp.
     */
    public function updatedAt(): void
    {
        $this->updated_at = time();
    }

    /**
     * Set the "created_at" timestamp.
     */
    public function createdAt(): void
    {
        $this->created_at = time();
    }

    /**
     * Check if the record has been soft deleted.
     *
     * @return bool True if the record has been soft deleted, false otherwise.
     */
    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    /**
     * Soft delete the record by setting the "deleted_at" timestamp.
     */
    public function softDeleteAt(): void
    {
        $this->deleted_at = time();
    }
}
