<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new'; case Contacted = 'contacted'; case FollowUp = 'follow_up'; case Qualified = 'qualified'; case Converted = 'converted'; case NotInterested = 'not_interested'; case Invalid = 'invalid'; case Closed = 'closed';
    public function label(): string { return str($this->value)->replace('_', ' ')->headline()->toString(); }
}
