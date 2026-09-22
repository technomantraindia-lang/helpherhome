<?php

namespace App\Enums;

enum WorkerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
    case Left = 'left';
}
