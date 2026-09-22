<?php
namespace App\Models; use Illuminate\Database\Eloquent\Attributes\Fillable; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['payment_id','old_status','new_status','changed_by','reason'])]
class PaymentStatusHistory extends Model { public $timestamps=false; protected $table='payment_status_history'; public function payment():BelongsTo{return $this->belongsTo(Payment::class);} public function changedBy():BelongsTo{return $this->belongsTo(User::class,'changed_by');} protected function casts():array{return ['created_at'=>'datetime'];} }
