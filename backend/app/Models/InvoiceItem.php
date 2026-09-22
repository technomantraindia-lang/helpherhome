<?php
namespace App\Models;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['invoice_id','sort_order','description','hsn_sac_code','quantity','unit','rate','amount','service_id','worker_id','notes'])]
class InvoiceItem extends Model {public function invoice():BelongsTo{return $this->belongsTo(Invoice::class);}public function service():BelongsTo{return $this->belongsTo(Service::class);}public function worker():BelongsTo{return $this->belongsTo(Worker::class);}protected function casts():array{return ['quantity'=>'decimal:2','rate'=>'decimal:2','amount'=>'decimal:2'];}}
