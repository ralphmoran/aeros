<?php

namespace App\Models;

use Aeros\Src\Classes\Model;
use App\Models\Role;
use Aeros\Src\Traits\Authenticable;

class User extends Model
{
    use Authenticable;

    /** @var int */
    private int $currentRole = 0;

    /** @var array */
    private array $roles = [];

    /** @var array */
    protected $fillable = ['username', 'fname', 'role'];

    /** @var array */
    protected $guarded = ['lname'];

    /**
     * User has roles.
     * 
     * This method will return the roles associated with the current user.
     *
     * @return void
     */
    public function roles()
    {
        return $this->hasMany(Role::class);
    }

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
