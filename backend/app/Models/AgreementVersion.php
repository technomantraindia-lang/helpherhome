<?php
namespace App\Models;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['agreement_id','version_number','snapshot_json','pdf_path','created_by'])]
class AgreementVersion extends Model {public $timestamps=false;public function agreement():BelongsTo{return $this->belongsTo(Agreement::class);}protected function casts():array{return ['snapshot_json'=>'array','created_at'=>'datetime'];}}
