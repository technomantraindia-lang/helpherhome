<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('invoices.create') ?? false; }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'customer_requirement_id' => ['nullable', 'exists:customer_requirements,id'],
            'assignment_id' => ['nullable', 'exists:assignments,id'],
            'agreement_id' => ['nullable', 'exists:agreements,id'],
            'invoice_date' => ['required', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'billing_period_start' => ['nullable', 'date'], 'billing_period_end' => ['nullable', 'date', 'after_or_equal:billing_period_start'],
            'customer_name_snapshot' => ['nullable', 'string', 'max:255'], 'customer_address_snapshot' => ['nullable', 'string', 'max:2000'],
            'customer_mobile_snapshot' => ['nullable', 'string', 'max:30'], 'customer_email_snapshot' => ['nullable', 'email', 'max:255'],
            'customer_gst_snapshot' => ['nullable', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1'], 'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.hsn_sac_code' => ['nullable', 'string', 'max:255'], 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'items.*.unit' => ['nullable', 'string', 'max:255'], 'items.*.rate' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.service_id' => ['nullable', 'exists:services,id'], 'items.*.worker_id' => ['nullable', 'exists:workers,id'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])], 'discount_value' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'tax_type' => ['required', Rule::in(['none', 'intra_state', 'inter_state'])], 'tax_rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'cgst_rate' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'], 'sgst_rate' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'],
            'igst_rate' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'], 'round_off' => ['nullable', 'numeric', 'between:-999999,999999', 'decimal:0,2'],
            'bank_holder_snapshot' => ['nullable', 'string', 'max:255'], 'bank_name_snapshot' => ['nullable', 'string', 'max:255'],
            'bank_account_snapshot' => ['nullable', 'string', 'max:255'], 'bank_ifsc_snapshot' => ['nullable', 'string', 'max:20'],
            'bank_branch_snapshot' => ['nullable', 'string', 'max:255'], 'upi_id_snapshot' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'], 'terms' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
