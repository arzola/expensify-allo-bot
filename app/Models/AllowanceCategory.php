<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowanceCategory extends Model
{
    protected $fillable = [
        'name',
        'expensify_category',
        'monthly_limit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'monthly_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
