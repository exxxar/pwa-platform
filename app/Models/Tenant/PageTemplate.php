<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'structure',
        'params',
    ];

    protected $casts = [
        'structure' => 'array',
        'params' => 'array',
    ];
}
