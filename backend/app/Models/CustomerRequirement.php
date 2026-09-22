<?php

namespace App\Models;

use App\Enums\RequirementStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['requirement_code', 'customer_id', 'service_id', 'duty_type_id', 'required_gender', 'number_of_persons', 'other_service_description', 'custom_duty_type', 'monthly_salary_budget', 'monthly_agency_service_charge', 'preferred_start_date', 'preferred_end_date', 'requirement_status', 'detailed_work_description', 'special_instructions', 'created_by', 'updated_by'])]
class CustomerRequirement extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (CustomerRequirement $requirement): void {
            if ($requirement->isDirty('requirement_code')) $requirement->requirement_code = $requirement->getOriginal('requirement_code');
        });
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function dutyType(): BelongsTo { return $this->belongsTo(DutyType::class); }
    public function householdDetail(): HasOne { return $this->hasOne(CustomerHouseholdDetail::class); }
    public function accommodationDetail(): HasOne { return $this->hasOne(CustomerAccommodationDetail::class); }
    public function workingCondition(): HasOne { return $this->hasOne(CustomerWorkingCondition::class); }
    public function workerPreference(): HasOne { return $this->hasOne(CustomerWorkerPreference::class); }
    public function statusHistory(): HasMany { return $this->hasMany(CustomerRequirementStatusHistory::class); }
    public function workerShortlists(): HasMany { return $this->hasMany(WorkerShortlist::class); }
    public function assignments(): HasMany { return $this->hasMany(Assignment::class); }
    public function replacementRequests(): HasMany { return $this->hasMany(ReplacementRequest::class); }
    public function agreements(): HasMany { return $this->hasMany(Agreement::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class, 'customer_requirement_id'); }
    public function generatedDocuments(): HasMany { return $this->hasMany(CustomerGeneratedDocument::class, 'customer_requirement_id'); }

    protected function casts(): array
    {
        return ['preferred_start_date' => 'date', 'preferred_end_date' => 'date', 'monthly_salary_budget' => 'decimal:2', 'monthly_agency_service_charge' => 'decimal:2', 'requirement_status' => RequirementStatus::class];
    }
}
