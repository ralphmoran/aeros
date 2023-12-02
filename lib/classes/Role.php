<?php

namespace Classes;

abstract class Role
{
    /** @var ?int */
    protected ?int $role = null;
    
    /** @var ?string */
    protected ?string $title = null;

    /** @var ?string */
    protected ?string $description = null;

    /**
     * Forces other children classes to assing a value to $role.
     */
    public final function __construct()
    {
        if ($this->role == null) {
            throw new \InvalidArgumentException(
                sprintf('ERROR[Role] %s: Protected parameter "$role" must be specified.', get_class($this))
            );
        }
    }

    /**
     * Returns the role value from child class.
     *
     * @return int|null
     */
    public static function value(): ?int
    {
        return (new static)->role;
    }
}
