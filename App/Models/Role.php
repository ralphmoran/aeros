<?php

namespace Aeros\App\Models;

use Aeros\Src\Classes\Model;

class Role extends Model
{
    /** @var array */
    protected $fillable = ['role', 'title', 'description'];
}
