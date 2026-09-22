<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
