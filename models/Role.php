<?php

namespace Aeros\Models;

use Aeros\Lib\Classes\Model;

class Role extends Model
{
    /** @var array */
    protected $fillable = ['role', 'title', 'description'];
}
