<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case Open          = 'open';
    case WorkerSearch  = 'worker_search';
    case Shortlisted   = 'shortlisted';
    case Assigned      = 'assigned';
    case Completed     = 'completed';
    case OnHold        = 'on_hold';
    case Cancelled     = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Open         => 'Open',
            self::WorkerSearch => 'Worker Search',
            self::Shortlisted  => 'Shortlisted',
            self::Assigned     => 'Assigned',
            self::Completed    => 'Completed',
            self::OnHold       => 'On Hold',
            self::Cancelled    => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Open         => 'badge-gold',
            self::WorkerSearch => 'badge-blue',
            self::Shortlisted  => 'badge-purple',
            self::Assigned     => 'badge-green',
            self::Completed    => 'badge-neutral',
            self::OnHold       => 'badge-orange',
            self::Cancelled    => 'badge-red',
        };
    }

    /** Statuses that represent "active / needs action" */
    public static function openStatuses(): array
    {
        return [self::Open->value, self::WorkerSearch->value, self::Shortlisted->value];
    }
}
