<?php

namespace App\Data;

use App\Enums\EligibilityStatus;

final readonly class EvaluationResult
{
    public function __construct(public EligibilityStatus $status, public array $criteria, public array $academicRoutes, public ?array $rankingRoute, public ?float $academicMargin) {}

    public function displayMargin(): string
    {
        return $this->academicMargin === null ? 'N/A' : number_format($this->academicMargin, 1).'%';
    }
}
