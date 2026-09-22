<?php
namespace App\Http\Requests; use Illuminate\Foundation\Http\FormRequest;
class CompletePaymentRequest extends FormRequest { public function authorize():bool{return $this->user()?->hasPermission('payments.complete')??false;} public function rules():array{return ['reason'=>['nullable','string','max:2000']];} }
