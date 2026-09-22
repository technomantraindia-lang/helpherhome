<?php
namespace App\Models;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;
#[Fillable(['token','document_type','document_id','expires_at','is_active','created_by'])]
class DocumentShareLink extends Model {public $timestamps=false;protected function casts():array{return ['expires_at'=>'datetime','is_active'=>'boolean','created_at'=>'datetime'];}}
