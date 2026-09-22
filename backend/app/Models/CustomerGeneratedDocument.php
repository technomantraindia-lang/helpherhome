<?php

namespace App\Models;

use App\Enums\CustomerGeneratedDocumentStatus;
use App\Enums\CustomerGeneratedDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'customer_requirement_id', 'document_type', 'document_number', 'version_number', 'status', 'snapshot_json', 'pdf_path', 'generated_at', 'generated_by', 'shared_at'])]
class CustomerGeneratedDocument extends Model
{
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function requirement(): BelongsTo { return $this->belongsTo(CustomerRequirement::class, 'customer_requirement_id'); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }

    protected function casts(): array
    {
        return ['document_type' => CustomerGeneratedDocumentType::class, 'status' => CustomerGeneratedDocumentStatus::class, 'snapshot_json' => 'array', 'generated_at' => 'datetime', 'shared_at' => 'datetime'];
    }
}
