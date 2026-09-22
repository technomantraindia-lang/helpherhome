<?php

namespace App\Http\Requests\Concerns;

trait NormalizesPhoneNumbers
{
    protected function digits(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        return preg_replace('/\D+/', '', $value) ?: null;
    }
}
