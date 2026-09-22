<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['enquiry_code','name','mobile_number','email','city','area','service_id','duty_type_id','preferred_start_date','required_gender','number_of_persons','preferred_contact_method','message','source','page_url','referrer','utm_source','utm_medium','utm_campaign','utm_term','utm_content','status','assigned_to','first_contacted_at','last_contacted_at','converted_customer_id','converted_requirement_id','converted_at','converted_by','notes','ip_address','user_agent'])]
class Enquiry extends Model
{
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function dutyType(): BelongsTo { return $this->belongsTo(DutyType::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function converter(): BelongsTo { return $this->belongsTo(User::class, 'converted_by'); }
    public function convertedCustomer(): BelongsTo { return $this->belongsTo(Customer::class, 'converted_customer_id'); }
    public function convertedRequirement(): BelongsTo { return $this->belongsTo(CustomerRequirement::class, 'converted_requirement_id'); }
    public function followUps(): HasMany { return $this->hasMany(EnquiryFollowUp::class); }
    protected function casts(): array { return ['preferred_start_date'=>'date','status'=>EnquiryStatus::class,'first_contacted_at'=>'datetime','last_contacted_at'=>'datetime','converted_at'=>'datetime']; }
}
