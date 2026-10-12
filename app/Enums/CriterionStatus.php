<?php

namespace App\Enums;

enum CriterionStatus: string
{
    case Met = 'Met';
    case NotMet = 'Not Met';
    case Manual = 'Manual Verification Required';
    case NotApplicable = 'Not Applicable';
}
