<?php

namespace Models;

use Classes\Role;
use Classes\Model;
use Traits\Authentication;

class User extends Model
{
    use Authentication;

    /** @var int */
    private int $currentRole = 0;

    /** @var array */
    private array $roles = [];

    protected $fillable = ['username', 'fname'];

    protected $guarded = ['lname'];

    public function __construct()
    {
        // Get user role value from persistent DB
        // Assing user value to user instance
    }

    /**
     * Assigns a role to a user.
     *
     * @param Role|int|string $role
     * @return void
     */
    public function addRole(Role|int|string $role)
    {
        if (is_int($role)) {
            $this->role |= $role;
        }

        if (is_string($role)) {

        }

        if ($role instanceof Role) {

        }

        $this->role |= $role;
    }

    /**
     * Checks if a user has a specific role.
     *
     * @param Role|int $role
     * @return boolean
     */
    public function hasRole(Role|int $role): bool
    {
        return ($this->role & $role) === $role;
    }

    /**
     * Remove a role from a user.
     *
     * @param Role|int $role
     * @return void
     */
    public function removeRole(Role|int $role)
    {
        $this->role &= ~$role;
    }
}
