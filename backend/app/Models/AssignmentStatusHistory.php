<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['assignment_id','old_status','new_status','changed_by','reason'])]
class AssignmentStatusHistory extends Model { protected $table='assignment_status_history';public $timestamps=false;public function assignment():BelongsTo{return $this->belongsTo(Assignment::class);}public function changedBy():BelongsTo{return $this->belongsTo(User::class,'changed_by');}protected function casts():array{return ['created_at'=>'datetime'];} }
