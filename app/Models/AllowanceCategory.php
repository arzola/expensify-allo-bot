<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowanceCategory extends Model
{
    protected $fillable = [
        'name',
        'annual_limit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'annual_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
