<?php
namespace App\Models;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['invoice_id','version_number','snapshot_json','pdf_path','created_by'])]
class InvoiceVersion extends Model {public $timestamps=false;public function invoice():BelongsTo{return $this->belongsTo(Invoice::class);}protected function casts():array{return ['snapshot_json'=>'array','created_at'=>'datetime'];}}
