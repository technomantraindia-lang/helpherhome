<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class GenerateInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('invoices.generate') ?? false; }
    public function rules(): array { return ['confirm_regenerate' => ['sometimes', 'accepted']]; }
}
