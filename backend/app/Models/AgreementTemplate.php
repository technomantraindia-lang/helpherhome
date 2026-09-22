<?php
namespace App\Models;use Illuminate\Database\Eloquent\Attributes\Fillable;use Illuminate\Database\Eloquent\Model;
#[Fillable(['name','slug','title','intro_text','terms_content','is_default','is_active'])]
class AgreementTemplate extends Model {protected function casts():array{return ['is_default'=>'boolean','is_active'=>'boolean'];}}
