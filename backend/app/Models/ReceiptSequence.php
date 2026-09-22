<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class ReceiptSequence extends Model { protected $primaryKey='year'; public $incrementing=false; protected $keyType='int'; protected $guarded=[]; }
