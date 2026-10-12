<?php

namespace App\Models;

use Database\Factories\IncomeCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    /** @use HasFactory<IncomeCategoryFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'minimum' => 'decimal:2', 'maximum' => 'decimal:2'];
    }
}
