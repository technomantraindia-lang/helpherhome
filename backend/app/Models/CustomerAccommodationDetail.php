<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['customer_requirement_id','accommodation_provided','room_type','bathroom_type','food_provided','breakfast_provided','lunch_provided','dinner_provided','tea_snacks_provided','other_food_details','accommodation_notes'])]
class CustomerAccommodationDetail extends Model
{
    public function requirement(): BelongsTo { return $this->belongsTo(CustomerRequirement::class, 'customer_requirement_id'); }
    protected function casts(): array { return ['breakfast_provided'=>'boolean','lunch_provided'=>'boolean','dinner_provided'=>'boolean','tea_snacks_provided'=>'boolean']; }
}
