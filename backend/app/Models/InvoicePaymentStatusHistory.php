<?php
namespace App\Models;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['invoice_id','old_status','new_status','paid_amount','balance_amount','changed_by','reason'])]
class InvoicePaymentStatusHistory extends Model {public $timestamps=false;protected $table='invoice_payment_status_history';public function invoice():BelongsTo{return $this->belongsTo(Invoice::class);}public function changedBy():BelongsTo{return $this->belongsTo(User::class,'changed_by');}protected function casts():array{return ['created_at'=>'datetime','paid_amount'=>'decimal:2','balance_amount'=>'decimal:2'];}}
