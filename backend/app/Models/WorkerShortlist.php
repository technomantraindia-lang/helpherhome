<?php
namespace App\Models;
use App\Enums\ShortlistStatus;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['customer_requirement_id','worker_id','shortlisted_by','shortlisted_at','status','notes'])]
class WorkerShortlist extends Model { public function requirement():BelongsTo{return $this->belongsTo(CustomerRequirement::class,'customer_requirement_id');} public function worker():BelongsTo{return $this->belongsTo(Worker::class);} public function shortlister():BelongsTo{return $this->belongsTo(User::class,'shortlisted_by');} protected function casts():array{return ['shortlisted_at'=>'datetime','status'=>ShortlistStatus::class];} }
