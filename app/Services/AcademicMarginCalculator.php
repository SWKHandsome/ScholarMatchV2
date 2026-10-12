<?php

namespace App\Services;

use App\Enums\CriterionStatus;

class AcademicMarginCalculator
{
    public function best(array $routes): ?array
    {
        $best = null;
        foreach ($routes as $route) {
            if ($route['status'] !== CriterionStatus::Met || $route['minimum'] === null || $route['minimum'] <= 0 || $route['result'] === null) {
                continue;
            }
            $margin = (($route['result'] / $route['minimum']) - 1) * 100;
            if ($best === null || $margin > $best['margin']) {
                $best = $route + ['margin' => $margin];
            }
        }

        return $best;
    }
}
