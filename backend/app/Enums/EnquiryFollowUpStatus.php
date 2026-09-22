<?php

namespace App\Enums;

enum EnquiryFollowUpStatus: string
{
    case Pending = 'pending'; case Completed = 'completed'; case Cancelled = 'cancelled';
    public function label(): string { return str($this->value)->headline()->toString(); }
}
