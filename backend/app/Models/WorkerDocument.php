<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['worker_id', 'document_type', 'document_name', 'document_number', 'file_path', 'issue_date', 'expiry_date', 'verification_status', 'verified_by', 'verified_at', 'remarks'])]
class WorkerDocument extends Model
{
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date', 'verified_at' => 'datetime', 'verification_status' => VerificationStatus::class];
    }
}
