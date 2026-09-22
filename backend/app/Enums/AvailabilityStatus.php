<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case Working = 'working';
    case Unavailable = 'unavailable';
}
