<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['worker_id', 'name', 'mobile_number', 'relation', 'address', 'occupation', 'notes'])]
class WorkerReference extends Model
{
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
