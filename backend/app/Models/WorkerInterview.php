<?php

namespace App\Models;

use App\Enums\InterviewResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['worker_id', 'interview_date', 'interviewer_user_id', 'service_id', 'experience_assessment', 'skills_assessment', 'communication_assessment', 'expected_salary', 'preferred_duty_type_id', 'preferred_location', 'interview_notes', 'result'])]
class WorkerInterview extends Model
{
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function interviewer(): BelongsTo { return $this->belongsTo(User::class, 'interviewer_user_id'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function preferredDutyType(): BelongsTo { return $this->belongsTo(DutyType::class, 'preferred_duty_type_id'); }

    protected function casts(): array
    {
        return ['interview_date' => 'datetime', 'expected_salary' => 'decimal:2', 'result' => InterviewResult::class];
    }
}
