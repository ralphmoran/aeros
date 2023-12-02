<?php
namespace Roles;

use Classes\Role;

class GuestRole extends Role
{
    /** @var ?int */
    protected ?int $role = 1; // Bit 1

    /** @var ?string */
    protected ?string $title = 'Guest Role';
}
