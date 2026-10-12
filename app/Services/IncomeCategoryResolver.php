<?php

namespace App\Services;

use App\Models\IncomeCategory;
use Illuminate\Support\Collection;

class IncomeCategoryResolver
{
    public function resolve(?float $income, Collection $categories, ?int $year = null): ?IncomeCategory
    {
        if ($income === null) {
            return null;
        }
        $active = $categories->where('is_active', true);
        $year ??= $active->max('reference_year');
        $matches = $active->where('reference_year', $year)->filter(fn ($category) => $income >= (float) $category->minimum && ($category->maximum === null || $income <= (float) $category->maximum));

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
