<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'short_description', 'is_active', 'sort_order'])]
class Service extends Model
{
    use HasFactory;

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'worker_service')->withPivot(['skill_level', 'experience_years', 'notes']);
    }

    public function customerRequirements(): HasMany { return $this->hasMany(CustomerRequirement::class); }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
