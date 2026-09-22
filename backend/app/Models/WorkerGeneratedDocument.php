<?php

namespace App\Models;

use App\Enums\WorkerGeneratedDocumentStatus;
use App\Enums\WorkerGeneratedDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['worker_id', 'document_type', 'document_number', 'version_number', 'status', 'snapshot_json', 'pdf_path', 'generated_at', 'generated_by', 'shared_at'])]
class WorkerGeneratedDocument extends Model
{
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }

    protected function casts(): array
    {
        return [
            'document_type' => WorkerGeneratedDocumentType::class,
            'status' => WorkerGeneratedDocumentStatus::class,
            'snapshot_json' => 'array',
            'generated_at' => 'datetime',
            'shared_at' => 'datetime',
        ];
    }
}
