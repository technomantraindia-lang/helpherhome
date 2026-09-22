<?php
namespace App\Http\Requests;class UpdateAgreementRequest extends StoreAgreementRequest {public function authorize():bool{return $this->user()?->hasPermission('agreements.edit')??false;}}
