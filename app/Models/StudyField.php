<?php

namespace App\Models;

use Database\Factories\StudyFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyField extends Model
{
    /** @use HasFactory<StudyFieldFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
