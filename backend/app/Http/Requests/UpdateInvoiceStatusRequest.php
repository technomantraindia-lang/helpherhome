<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateInvoiceStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('invoices.share') ?? false; }
    public function rules(): array { return ['reason' => ['nullable', 'string', 'max:2000']]; }
}
