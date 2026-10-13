<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipAcademicRoute extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['minimum' => 'decimal:4', 'grading_scale' => 'decimal:4'];
    }

    public function resultRequirement(): string
    {
        if ($this->result_type === 'as_count') {
            if ($this->minimum === null) {
                return 'Required number of As needs verification';
            }

            return 'At least '.$this->formatResult((float) $this->minimum).' '.((float) $this->minimum === 1.0 ? 'A' : 'As');
        }

        if ($this->result_type === 'cgpa') {
            if ($this->minimum === null) {
                return 'CGPA minimum needs verification';
            }

            $minimum = $this->formatResult((float) $this->minimum, 2);

            return 'CGPA of at least '.$minimum.($this->grading_scale === null
                ? ' (grading scale needs verification)'
                : '/'.$this->formatResult((float) $this->grading_scale, 2));
        }

        return 'Results to verify with the provider';
    }

    public function requirementSummary(): string
    {
        $result = $this->resultRequirement();
        $summary = $this->qualification.' with '.(str_starts_with($result, 'CGPA') ? $result : lcfirst($result));

        return $summary.($this->manual_conditions ? '; '.$this->manual_conditions : '');
    }

    private function formatResult(float $value, int $minimumDecimals = 0): string
    {
        [$whole, $fraction] = explode('.', number_format($value, 4, '.', ''));
        $fraction = str_pad(rtrim($fraction, '0'), $minimumDecimals, '0');

        return $whole.($fraction === '' ? '' : '.'.$fraction);
    }
}
