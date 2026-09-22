<?php

namespace App\Enums;

enum WorkerGeneratedDocumentStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Shared = 'shared';
    case Archived = 'archived';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
