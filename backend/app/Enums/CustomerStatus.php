<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
            self::Blocked  => 'Blocked',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active   => 'badge-green',
            self::Inactive => 'badge-neutral',
            self::Blocked  => 'badge-red',
        };
    }
}
