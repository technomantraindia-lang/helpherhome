<?php

namespace App\Enums;

enum ExperienceRequirement: string
{
    case None        = 'none';
    case OneTwo      = '1_2_years';
    case TwoFive     = '2_5_years';
    case FivePlus    = '5_plus_years';
    case Custom      = 'custom';

    public function label(): string
    {
        return match($this) {
            self::None     => 'No Experience Required',
            self::OneTwo   => '1–2 Years',
            self::TwoFive  => '2–5 Years',
            self::FivePlus => '5+ Years',
            self::Custom   => 'Custom',
        };
    }
}
