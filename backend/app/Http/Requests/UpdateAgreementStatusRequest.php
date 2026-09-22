<?php
namespace App\Http\Requests;use App\Enums\AgreementStatus;use Illuminate\Foundation\Http\FormRequest;use Illuminate\Validation\Rule;
class UpdateAgreementStatusRequest extends FormRequest {public function authorize():bool{return $this->input('status')==='cancelled'?($this->user()?->hasPermission('agreements.cancel')??false):($this->user()?->hasPermission('agreements.edit')??false);}public function rules():array{return ['status'=>['required',Rule::enum(AgreementStatus::class)],'reason'=>['nullable','string','max:1000']];}}
