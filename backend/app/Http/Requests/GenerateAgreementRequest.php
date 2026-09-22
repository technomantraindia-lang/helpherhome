<?php
namespace App\Http\Requests;use Illuminate\Foundation\Http\FormRequest;
class GenerateAgreementRequest extends FormRequest {public function authorize():bool{return $this->user()?->hasPermission('agreements.generate')??false;}public function rules():array{return ['confirm_regenerate'=>['nullable','accepted']];}}
