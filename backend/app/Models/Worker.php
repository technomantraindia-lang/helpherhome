<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\WorkerStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'worker_code', 'registration_number', 'registration_date', 'date_of_joining', 'name', 'photo_path',
    'mobile_number', 'alternate_mobile_number', 'email', 'address_line_1', 'address_line_2', 'city', 'state',
    'pincode', 'country', 'date_of_birth', 'age', 'gender', 'marital_status', 'years_of_experience',
    'experience_notes', 'salary_expectation', 'preferred_work_location', 'preferred_duty_type_id',
    'availability_status', 'worker_status', 'police_station_name', 'supervisor_user_id', 'executive_user_id',
    'registration_source', 'notes', 'created_by', 'updated_by',
])]
class Worker extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (Worker $worker): void {
            if ($worker->isDirty('worker_code')) {
                $worker->worker_code = $worker->getOriginal('worker_code');
            }
        });
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'worker_service')->withPivot(['skill_level', 'experience_years', 'notes']);
    }

    public function references(): HasMany
    {
        return $this->hasMany(WorkerReference::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(WorkerDocument::class);
    }

    public function verification(): HasOne
    {
        return $this->hasOne(WorkerVerification::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(WorkerInterview::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(WorkerStatusHistory::class);
    }

    public function workerShortlists(): HasMany { return $this->hasMany(WorkerShortlist::class); }
    public function assignments(): HasMany { return $this->hasMany(Assignment::class); }
    public function replacementsAsOldWorker(): HasMany { return $this->hasMany(ReplacementRequest::class, 'old_worker_id'); }
    public function replacementsAsNewWorker(): HasMany { return $this->hasMany(ReplacementRequest::class, 'new_worker_id'); }
    public function agreements(): HasMany { return $this->hasMany(Agreement::class); }
    public function generatedDocuments(): HasMany { return $this->hasMany(WorkerGeneratedDocument::class); }

    public function preferredDutyType(): BelongsTo
    {
        return $this->belongsTo(DutyType::class, 'preferred_duty_type_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'registration_date' => 'date', 'date_of_joining' => 'date', 'date_of_birth' => 'date',
            'years_of_experience' => 'decimal:1', 'salary_expectation' => 'decimal:2',
            'availability_status' => AvailabilityStatus::class, 'worker_status' => WorkerStatus::class,
        ];
    }
}
