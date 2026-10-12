<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Scholarship extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['deadline' => 'immutable_date', 'last_verified_at' => 'immutable_datetime', 'is_fictional' => 'boolean'];
    }

    public function requirements(): HasOne
    {
        return $this->hasOne(ScholarshipRequirement::class);
    }

    public function academicRoutes(): HasMany
    {
        return $this->hasMany(ScholarshipAcademicRoute::class);
    }

    public function studyFields(): BelongsToMany
    {
        return $this->belongsToMany(StudyField::class, 'scholarship_study_fields');
    }

    public function incomeCategories(): BelongsToMany
    {
        return $this->belongsToMany(IncomeCategory::class, 'scholarship_income_categories');
    }

    public function closingInstant(): CarbonImmutable
    {
        return $this->closing_at ? CarbonImmutable::parse($this->closing_at, 'UTC') : $this->deadline->setTimezone('Asia/Kuala_Lumpur')->endOfDay();
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'inactive') {
            return 'inactive';
        }

        return $this->status === 'closed' || now()->greaterThan($this->closingInstant()) ? 'closed' : 'open';
    }

    public function scopeWithEvaluationData(Builder $query): Builder
    {
        return $query->with(['requirements', 'academicRoutes', 'studyFields', 'incomeCategories']);
    }
}
