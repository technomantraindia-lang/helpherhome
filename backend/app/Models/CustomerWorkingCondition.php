<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['customer_requirement_id','working_hours_text','work_start_time','work_end_time','expected_wakeup_time','expected_sleep_time','afternoon_rest','rest_duration_hours','monthly_leave_days','leave_details','night_duty_required','early_morning_work_required','early_morning_work_details'])]
class CustomerWorkingCondition extends Model
{
    public function requirement(): BelongsTo { return $this->belongsTo(CustomerRequirement::class, 'customer_requirement_id'); }
    protected function casts(): array { return ['afternoon_rest'=>'boolean','night_duty_required'=>'boolean','early_morning_work_required'=>'boolean','rest_duration_hours'=>'decimal:1']; }
}
