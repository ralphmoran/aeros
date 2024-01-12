<?php

namespace Aeros\Models;

use Aeros\Lib\Classes\Model;
use Aeros\Models\Role;
use Aeros\Lib\Traits\Authentication;

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
        if (! $this->hasRole($role)) {

            if (is_int($role)) {
                $this->role |= $role;

                return true;
            }

            if ($role instanceof Role) {
                $this->role |= intval($role->role);

                return true;
            }

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
        if ($this->roleExists($role)) { 

            if (is_int($role)) {
                return ($this->role & $role) === $role;
            }

            if ($role instanceof Role) {
                return ($this->role & intval($role->role)) === intval($role->role);
            }

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
        if ($this->hasRole($role)) {

            if (is_int($role)) {
                $this->role &= ~$role;

                return true;
            }

            if ($role instanceof Role) {
                $this->role &= ~intval($role->role);

                return true;
            }

        }

        return false;
    }

    /**
     * Checks if a role exists.
     *
     * @param integer $role
     * @return Role|boolean
     */
    public function roleExists(Role|int $role): bool
    {
        $roleValue = is_int($role) ? $role : $role->role;

        return Role::find([['role', '=', $roleValue]]) ? true : false;
    }
}
