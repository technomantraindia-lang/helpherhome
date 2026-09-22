<?php
namespace App\Models;
use App\Enums\ExperienceRequirement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['customer_requirement_id','preferred_age_min','preferred_age_max','experience_requirement','language_preference','specific_skills','other_work_expected','worker_preference_notes'])]
class CustomerWorkerPreference extends Model
{
    public function requirement(): BelongsTo { return $this->belongsTo(CustomerRequirement::class, 'customer_requirement_id'); }
    protected function casts(): array { return ['experience_requirement'=>ExperienceRequirement::class]; }
}
