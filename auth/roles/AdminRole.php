<?php
namespace Roles;

use Classes\Role;

class AdminRole extends Role
{
    /** @var ?int */
    protected ?int $role = 2; // Bit 2

    /** @var ?string */
    protected ?string $title = 'Admin Role';
}
