<?php
namespace Roles;

use Classes\Role;

class SuperRole extends Role
{
    /** @var ?int */
    protected ?int $role = 4; // Bit 3

    /** @var ?string */
    protected ?string $title = 'Super Role';
}
