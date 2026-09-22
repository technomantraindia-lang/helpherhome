<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['customer_requirement_id','house_type','custom_house_type','total_family_members','adults_count','children_count','elderly_count','patients_count','food_preference','pets_at_home','children_at_home','elderly_at_home','patient_care_required','lift_available','outside_travel_required'])]
class CustomerHouseholdDetail extends Model
{
    public function requirement(): BelongsTo { return $this->belongsTo(CustomerRequirement::class, 'customer_requirement_id'); }
    protected function casts(): array { return ['pets_at_home'=>'boolean','children_at_home'=>'boolean','elderly_at_home'=>'boolean','patient_care_required'=>'boolean','lift_available'=>'boolean','outside_travel_required'=>'boolean']; }
}
