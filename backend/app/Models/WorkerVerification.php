<?php

namespace App\Models;

use App\Enums\OverallVerificationStatus;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'worker_id', 'document_verification_status', 'document_verification_date', 'document_verified_by',
    'police_verification_status', 'police_verification_date', 'police_verified_by',
    'background_verification_status', 'background_verification_date', 'background_verified_by',
    'overall_verification_status', 'remarks',
])]
class WorkerVerification extends Model
{
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function documentVerifier(): BelongsTo { return $this->belongsTo(User::class, 'document_verified_by'); }
    public function policeVerifier(): BelongsTo { return $this->belongsTo(User::class, 'police_verified_by'); }
    public function backgroundVerifier(): BelongsTo { return $this->belongsTo(User::class, 'background_verified_by'); }

    public static function overallFor(array $statuses): OverallVerificationStatus
    {
        if (in_array(VerificationStatus::Rejected->value, $statuses, true)) return OverallVerificationStatus::Rejected;
        if (count(array_filter($statuses, fn ($status) => $status === VerificationStatus::Verified->value)) === 3) return OverallVerificationStatus::Verified;
        if (count(array_filter($statuses, fn ($status) => $status !== VerificationStatus::Pending->value)) > 0) return OverallVerificationStatus::PartiallyVerified;
        return OverallVerificationStatus::Pending;
    }

    protected function casts(): array
    {
        return [
            'document_verification_status' => VerificationStatus::class, 'police_verification_status' => VerificationStatus::class,
            'background_verification_status' => VerificationStatus::class, 'overall_verification_status' => OverallVerificationStatus::class,
            'document_verification_date' => 'date', 'police_verification_date' => 'date', 'background_verification_date' => 'date',
        ];
    }
}
