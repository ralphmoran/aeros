<?php

namespace Models;

use Classes\Model;
use Models\Role;
use Traits\Authentication;

class User extends Model
{
    use Authentication;

    /** @var int */
    private int $currentRole = 0;

    /** @var array */
    private array $roles = [];

    /** @var array */
    protected $fillable = ['username', 'fname', 'role'];

    /** @var array */
    protected $guarded = ['lname'];

    /**
     * Assigns a role to a user.
     *
     * @param Role|integer $role
     * @return bool
     */
    public function addRole(Role|int $role): bool
    {
        if (is_int($role) && $this->roleExists($role) && ! $this->hasRole($role)) {
            $this->role |= $role;

            return true;
        }

        if (($role instanceof Role) && $this->roleExists($role->role) && ! $this->hasRole($role)) {
            $this->role |= intval($role->role);

            return true;
        }

        return false;
    }

    /**
     * Checks if a user has a specific role.
     *
     * @param Role|integer $role
     * @return boolean
     */
    public function hasRole(Role|int $role): bool
    {
        if (is_int($role) && $this->roleExists($role)) {
            return ($this->role & $role) === $role;
        }

        if (($role instanceof Role) && $this->roleExists($role->role)) {
            return ($this->role & intval($role->role)) === intval($role->role);
        }

        return false;
    }

    /**
     * Remove a role from a user.
     *
     * @param Role|integer $role
     * @return bool
     */
    public function removeRole(Role|int $role): bool
    {
        if (is_int($role) && $this->roleExists($role) && $this->hasRole($role)) {
            $this->role &= ~$role;

            return true;
        }

        if (($role instanceof Role) && $this->roleExists($role->role) && $this->hasRole(intval($role->role))) {
            $this->role &= ~intval($role->role);

            return true;
        }

        return false;
    }

    /**
     * Checks if a role exists.
     *
     * @param integer $role
     * @return boolean
     */
    public function roleExists(int $role): bool
    {
        return Role::find([['role', '=', $role]]) ? true : false;
    }
}
