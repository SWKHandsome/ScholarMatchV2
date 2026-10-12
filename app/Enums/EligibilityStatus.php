<?php

namespace App\Enums;

enum EligibilityStatus: string
{
    case Eligible = 'Eligible';
    case Manual = 'Manual Verification Required';
    case NotEligible = 'Not Eligible';
}
