<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['worker_id', 'old_status', 'new_status', 'changed_by', 'reason'])]
class WorkerStatusHistory extends Model
{
    protected $table = 'worker_status_history';
    public const UPDATED_AT = null;
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
