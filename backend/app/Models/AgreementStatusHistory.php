<?php
namespace App\Models;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['agreement_id','old_status','new_status','changed_by','reason'])]
class AgreementStatusHistory extends Model {protected $table='agreement_status_history';public $timestamps=false;public function agreement():BelongsTo{return $this->belongsTo(Agreement::class);}public function changedBy():BelongsTo{return $this->belongsTo(User::class,'changed_by');}protected function casts():array{return ['created_at'=>'datetime'];}}
