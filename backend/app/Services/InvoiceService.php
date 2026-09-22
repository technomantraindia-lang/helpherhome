<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AgencySetting;
use App\Models\Agreement;
use App\Models\Assignment;
use App\Models\Customer;
use App\Models\CustomerRequirement;
use App\Models\Invoice;
use App\Models\InvoiceSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function create(array $data, int $actorId): Invoice
    {
        for ($attempt = 0; $attempt < 12; $attempt++) {
            try {
                return $this->createInTransaction($data, $actorId);
            } catch (QueryException $e) {
                if (DB::getDriverName() !== 'sqlite' || !str_contains($e->getMessage(), 'database is locked') || $attempt === 11) throw $e;
                usleep(random_int(20000, 70000) * ($attempt + 1));
            }
        }
        throw new \LogicException('Invoice creation retry limit reached.');
    }

    private function createInTransaction(array $data, int $actorId): Invoice
    {
        return DB::transaction(function () use ($data, $actorId) {
            $related = $this->resolve($data);
            $agency = AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home']);
            $year = (int) date('Y', strtotime($data['invoice_date']));
            InvoiceSequence::query()->insertOrIgnore(['year' => $year, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $sequence = InvoiceSequence::whereKey($year)->lockForUpdate()->firstOrFail();
            $sequence->increment('last_number');
            $number = sprintf('HH-INV-%d-%06d', $year, $sequence->last_number);
            $invoice = Invoice::create($this->attributes($data, $related, $agency) + [
                'invoice_number' => $number, 'status' => InvoiceStatus::Draft, 'payment_status' => PaymentStatus::Unpaid,
                'paid_amount' => '0.00', 'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            $this->saveItems($invoice, $data['items']);
            $this->copyAssets($invoice, $agency);
            $invoice->statusHistory()->create(['new_status' => 'draft', 'changed_by' => $actorId, 'reason' => 'Invoice created']);
            return $invoice->fresh('items');
        }, 5);
    }

    public function update(Invoice $invoice, array $data, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $actorId) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status === InvoiceStatus::Cancelled || $invoice->payment_status !== PaymentStatus::Unpaid || bccomp($invoice->paid_amount, '0', 2) > 0) {
                throw ValidationException::withMessages(['invoice' => 'Paid, partially paid, or cancelled invoices cannot be edited.']);
            }
            if ($invoice->status !== InvoiceStatus::Draft && empty($data['confirm_regenerate'])) {
                throw ValidationException::withMessages(['confirm_regenerate' => 'Confirm that editing this invoice will require a new PDF version.']);
            }
            $related = $this->resolve($data);
            $invoice->update($this->attributes($data, $related, null, $invoice) + ['updated_by' => $actorId]);
            $invoice->items()->delete();
            $this->saveItems($invoice, $data['items']);
            return $invoice->fresh('items');
        });
    }

    public function transition(Invoice $invoice, InvoiceStatus $to, int $actorId, ?string $reason = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $to, $actorId, $reason) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $from = $invoice->status->value;
            $allowed = ['draft' => ['generated', 'cancelled'], 'generated' => ['sent', 'cancelled'], 'sent' => ['cancelled'], 'cancelled' => []];
            if (!in_array($to->value, $allowed[$from], true)) throw ValidationException::withMessages(['status' => 'Invalid invoice status transition.']);
            if ($to === InvoiceStatus::Cancelled && (bccomp($invoice->paid_amount, '0', 2) > 0 || $invoice->payment_status === PaymentStatus::Paid)) {
                throw ValidationException::withMessages(['status' => 'An invoice with recorded payment cannot be cancelled.']);
            }
            if ($to === InvoiceStatus::Sent && !$invoice->pdf_path) throw ValidationException::withMessages(['status' => 'Generate the PDF before marking sent.']);
            $invoice->update(['status' => $to, 'updated_by' => $actorId]);
            $invoice->statusHistory()->create(['old_status' => $from, 'new_status' => $to->value, 'changed_by' => $actorId, 'reason' => $reason]);
            return $invoice->fresh();
        });
    }

    private function resolve(array $data): array
    {
        $customer = Customer::findOrFail($data['customer_id']);
        $agreement = !empty($data['agreement_id']) ? Agreement::findOrFail($data['agreement_id']) : null;
        $assignment = !empty($data['assignment_id']) ? Assignment::findOrFail($data['assignment_id']) : null;
        $requirement = !empty($data['customer_requirement_id']) ? CustomerRequirement::findOrFail($data['customer_requirement_id']) : null;
        if ($agreement && $agreement->customer_id !== $customer->id) throw ValidationException::withMessages(['agreement_id' => 'Agreement belongs to another customer.']);
        if ($agreement && $assignment && $agreement->assignment_id !== $assignment->id) throw ValidationException::withMessages(['assignment_id' => 'Assignment does not match agreement.']);
        $assignment ??= $agreement?->assignment;
        if ($assignment && $assignment->customer_id !== $customer->id) throw ValidationException::withMessages(['assignment_id' => 'Assignment belongs to another customer.']);
        if ($agreement?->customer_requirement_id && $requirement && $agreement->customer_requirement_id !== $requirement->id) throw ValidationException::withMessages(['customer_requirement_id' => 'Requirement does not match agreement.']);
        $requirement ??= $assignment?->requirement ?? $agreement?->requirement;
        if ($requirement && $requirement->customer_id !== $customer->id) throw ValidationException::withMessages(['customer_requirement_id' => 'Requirement belongs to another customer.']);
        if ($assignment && $requirement && $assignment->customer_requirement_id !== $requirement->id) throw ValidationException::withMessages(['customer_requirement_id' => 'Requirement does not match assignment.']);
        return compact('customer', 'agreement', 'assignment', 'requirement');
    }

    private function attributes(array $data, array $related, ?AgencySetting $agency, ?Invoice $existing = null): array
    {
        $customer = $related['customer'];
        $subtotal = '0.00';
        foreach ($data['items'] as $item) $subtotal = bcadd($subtotal, $this->money(bcmul((string) $item['quantity'], (string) $item['rate'], 4)), 2);
        $discountType = $data['discount_type'] ?? null;
        $discountValue = (string) ($data['discount_value'] ?? 0);
        if ($discountType === null && bccomp($discountValue, '0', 2) > 0) throw ValidationException::withMessages(['discount_type' => 'Choose a discount type.']);
        if ($discountType === 'percentage' && bccomp($discountValue, '100', 2) > 0) throw ValidationException::withMessages(['discount_value' => 'Percentage cannot exceed 100.']);
        $discount = $discountType === 'percentage' ? $this->money(bcdiv(bcmul($subtotal, $discountValue, 4), '100', 4)) : ($discountType === 'fixed' ? $discountValue : '0.00');
        if (bccomp($discount, $subtotal, 2) > 0) throw ValidationException::withMessages(['discount_value' => 'Discount cannot exceed subtotal.']);
        $taxable = bcsub($subtotal, $discount, 2);
        $taxType = $data['tax_type'];
        $rate = (string) $data['tax_rate'];
        $cgst = (string) ($data['cgst_rate'] ?? 0); $sgst = (string) ($data['sgst_rate'] ?? 0); $igst = (string) ($data['igst_rate'] ?? 0);
        if ($taxType === 'none') { $rate = $cgst = $sgst = $igst = '0.00'; }
        if ($taxType === 'intra_state') {
            if (bccomp(bcadd($cgst, $sgst, 2), $rate, 2) !== 0 || bccomp($igst, '0', 2) !== 0) throw ValidationException::withMessages(['tax_rate' => 'CGST plus SGST must equal GST rate; IGST must be zero.']);
        }
        if ($taxType === 'inter_state') {
            if (bccomp($igst, $rate, 2) !== 0 || bccomp($cgst, '0', 2) !== 0 || bccomp($sgst, '0', 2) !== 0) throw ValidationException::withMessages(['tax_rate' => 'IGST must equal GST rate; CGST and SGST must be zero.']);
        }
        $tax = fn(string $r) => $this->money(bcdiv(bcmul($taxable, $r, 4), '100', 4));
        $cgstAmount = $tax($cgst); $sgstAmount = $tax($sgst); $igstAmount = $tax($igst);
        $taxTotal = bcadd(bcadd($cgstAmount, $sgstAmount, 2), $igstAmount, 2);
        $roundOff = (string) ($data['round_off'] ?? 0);
        $total = bcadd(bcadd($taxable, $taxTotal, 2), $roundOff, 2);
        if (bccomp($total, '0', 2) < 0) throw ValidationException::withMessages(['round_off' => 'Final total cannot be negative.']);
        $paid = $existing?->paid_amount ?? '0.00';
        $attributes = [
            'customer_id' => $customer->id, 'agreement_id' => $related['agreement']?->id,
            'assignment_id' => $related['assignment']?->id, 'customer_requirement_id' => $related['requirement']?->id,
            'invoice_date' => $data['invoice_date'], 'due_date' => $data['due_date'] ?? null,
            'billing_period_start' => $data['billing_period_start'] ?? null, 'billing_period_end' => $data['billing_period_end'] ?? null,
            'subtotal' => $subtotal, 'discount_type' => $discountType, 'discount_value' => $discountValue, 'discount_amount' => $discount,
            'tax_type' => $taxType, 'tax_rate' => $rate, 'cgst_rate' => $cgst, 'cgst_amount' => $cgstAmount,
            'sgst_rate' => $sgst, 'sgst_amount' => $sgstAmount, 'igst_rate' => $igst, 'igst_amount' => $igstAmount,
            'tax_total' => $taxTotal, 'round_off' => $roundOff, 'total_amount' => $total, 'balance_amount' => bcsub($total, $paid, 2),
            'customer_name_snapshot' => $data['customer_name_snapshot'] ?? $customer->name,
            'customer_address_snapshot' => $data['customer_address_snapshot'] ?? collect([$customer->address_line_1, $customer->address_line_2, $customer->location, $customer->city, $customer->state, $customer->pincode, $customer->country])->filter()->join(', '),
            'customer_mobile_snapshot' => $data['customer_mobile_snapshot'] ?? $customer->mobile_number,
            'customer_email_snapshot' => $data['customer_email_snapshot'] ?? $customer->email,
            'customer_gst_snapshot' => $data['customer_gst_snapshot'] ?? null,
            'notes' => $data['notes'] ?? null, 'terms' => $data['terms'] ?? null,
        ];
        foreach (['bank_holder_snapshot', 'bank_name_snapshot', 'bank_account_snapshot', 'bank_ifsc_snapshot', 'bank_branch_snapshot', 'upi_id_snapshot'] as $key) {
            $attributes[$key] = $data[$key] ?? $existing?->$key;
        }
        if ($agency) $attributes = array_merge($attributes, [
            'agency_name_snapshot' => $agency->business_name,
            'agency_address_snapshot' => collect([$agency->address_line_1, $agency->address_line_2, $agency->city, $agency->state, $agency->pincode, $agency->country])->filter()->join(', '),
            'agency_phone_snapshot' => $agency->phone_primary, 'agency_email_snapshot' => $agency->email, 'agency_gst_snapshot' => $agency->gst_number,
            'bank_holder_snapshot' => $data['bank_holder_snapshot'] ?? $agency->bank_account_holder_name,
            'bank_name_snapshot' => $data['bank_name_snapshot'] ?? $agency->bank_name,
            'bank_account_snapshot' => $data['bank_account_snapshot'] ?? $agency->bank_account_number,
            'bank_ifsc_snapshot' => $data['bank_ifsc_snapshot'] ?? $agency->bank_ifsc,
            'bank_branch_snapshot' => $data['bank_branch_snapshot'] ?? $agency->bank_branch,
            'upi_id_snapshot' => $data['upi_id_snapshot'] ?? $agency->upi_id,
        ]);
        return $attributes;
    }

    private function saveItems(Invoice $invoice, array $items): void
    {
        foreach (array_values($items) as $i => $item) $invoice->items()->create([
            'sort_order' => $i + 1, 'description' => $item['description'], 'hsn_sac_code' => $item['hsn_sac_code'] ?? null,
            'quantity' => $item['quantity'], 'unit' => $item['unit'] ?? null, 'rate' => $item['rate'],
            'amount' => $this->money(bcmul((string) $item['quantity'], (string) $item['rate'], 4)),
            'service_id' => $item['service_id'] ?? null, 'worker_id' => $item['worker_id'] ?? null, 'notes' => $item['notes'] ?? null,
        ]);
    }

    private function copyAssets(Invoice $invoice, AgencySetting $agency): void
    {
        foreach (['logo_path' => 'agency_logo_snapshot', 'signature_path' => 'signature_path_snapshot', 'stamp_path' => 'stamp_path_snapshot', 'upi_qr_path' => 'upi_qr_snapshot'] as $source => $target) {
            $disk = $source === 'logo_path' ? 'public' : (Storage::disk('local')->exists((string) $agency->$source) ? 'local' : 'public');
            if ($agency->$source && Storage::disk($disk)->exists($agency->$source)) {
                $ext = pathinfo($agency->$source, PATHINFO_EXTENSION) ?: 'png';
                $path = "invoices/{$invoice->invoice_number}/assets/{$target}.{$ext}";
                Storage::disk('local')->put($path, Storage::disk($disk)->get($agency->$source));
                $invoice->forceFill([$target => $path])->saveQuietly();
            }
        }
    }

    private function money(string $value): string
    {
        return bcadd($value, bccomp($value, '0', 4) < 0 ? '-0.005' : '0.005', 2);
    }
}
