<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class CancelInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('invoices.cancel') ?? false; }
    public function rules(): array { return ['reason' => ['required', 'string', 'max:2000']]; }
}
