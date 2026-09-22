<?php

namespace App\Models;

use App\Enums\EnquiryFollowUpStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enquiry_id','follow_up_date','follow_up_time','status','notes','assigned_to','completed_at','completed_by','created_by'])]
class EnquiryFollowUp extends Model
{
    public function enquiry(): BelongsTo { return $this->belongsTo(Enquiry::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    protected function casts(): array { return ['follow_up_date'=>'date','completed_at'=>'datetime','status'=>EnquiryFollowUpStatus::class]; }
}
