<?php

namespace App\Enums;

enum InterviewResult: string
{
    case Pending = 'pending';
    case Selected = 'selected';
    case Hold = 'hold';
    case Rejected = 'rejected';
}
