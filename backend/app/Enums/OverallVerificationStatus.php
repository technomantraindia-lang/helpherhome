<?php

namespace App\Enums;

enum OverallVerificationStatus: string
{
    case Pending = 'pending';
    case PartiallyVerified = 'partially_verified';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
