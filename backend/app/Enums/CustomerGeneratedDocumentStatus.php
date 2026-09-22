<?php

namespace App\Enums;

enum CustomerGeneratedDocumentStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Shared = 'shared';
    case Archived = 'archived';

    public function label(): string { return str($this->value)->headline()->toString(); }
}
