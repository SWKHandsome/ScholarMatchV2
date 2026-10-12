<?php

namespace App\Data;

use App\Enums\CriterionStatus;

final readonly class CriterionResult
{
    public function __construct(public string $key, public string $label, public CriterionStatus $status, public string $requirement, public string $studentValue, public string $explanation, public ?string $nextStep = null) {}
}
