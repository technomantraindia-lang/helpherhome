<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['replacement_request_id','old_status','new_status','changed_by','reason'])]
class ReplacementStatusHistory extends Model { protected $table='replacement_status_history';public $timestamps=false;public function replacement():BelongsTo{return $this->belongsTo(ReplacementRequest::class,'replacement_request_id');}public function changedBy():BelongsTo{return $this->belongsTo(User::class,'changed_by');}protected function casts():array{return ['created_at'=>'datetime'];} }
