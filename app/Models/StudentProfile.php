<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['academic_records_complete' => 'boolean', 'study_field_undecided' => 'boolean', 'household_income' => 'decimal:2'];
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(StudentAcademicRecord::class);
    }

    public function studyField(): BelongsTo
    {
        return $this->belongsTo(StudyField::class);
    }

    public function completeness(): int
    {
        return (int) round((9 - count($this->missingInformation())) / 9 * 100);
    }

    public function missingInformation(): array
    {
        $missing = [];
        foreach (['citizenship' => 'citizenship', 'residence_state' => 'state of residence', 'current_education_level' => 'current education', 'funding_level' => 'funding study level', 'institution_type' => 'institution type', 'study_location' => 'study location', 'household_income' => 'household income'] as $key => $label) {
            if ($this->$key === null || $this->$key === '') {
                $missing[] = $label;
            }
        }
        if (! $this->study_field_id && ! $this->study_field_undecided) {
            $missing[] = 'study field';
        }
        if (! $this->academic_records_complete) {
            $missing[] = 'confirmation of academic records';
        }

        return $missing;
    }
}
