<?php
namespace App\Http\Requests; use Illuminate\Foundation\Http\FormRequest;
class GeneratePaymentReceiptRequest extends FormRequest { public function authorize():bool{return $this->user()?->hasPermission('payment-receipts.generate')??false;} public function rules():array{return ['confirm_regenerate'=>['sometimes','accepted']];} }
