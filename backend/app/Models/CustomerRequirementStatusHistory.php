<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['customer_requirement_id','old_status','new_status','changed_by','reason'])]
class CustomerRequirementStatusHistory extends Model
{
    protected $table = 'customer_requirement_status_history';
    public $timestamps = false;
    public function requirement(): BelongsTo { return $this->belongsTo(CustomerRequirement::class, 'customer_requirement_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
    protected function casts(): array { return ['created_at'=>'datetime']; }
}
