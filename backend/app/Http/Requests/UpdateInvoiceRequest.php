<?php
namespace App\Http\Requests;
class UpdateInvoiceRequest extends StoreInvoiceRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('invoices.edit') ?? false; }
    public function rules(): array { return parent::rules() + ['confirm_regenerate' => ['sometimes', 'accepted']]; }
}
