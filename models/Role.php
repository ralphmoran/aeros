<?php

namespace Models;

use Classes\Model;

class Role extends Model
{
    /** @var array */
    protected $fillable = ['role', 'title', 'description'];
}
