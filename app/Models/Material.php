<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'jenjang',
        'mapel',
        'curriculum',
        'subelement',
        'unit',
        'sub_unit',
        'link',
    ];
}
