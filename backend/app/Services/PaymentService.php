<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Models\AgencySetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\PaymentSequence;
use App\Models\ReceiptSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function createPayment(Invoice $invoice, array $data, int $actorId): Payment
    {
        for ($attempt = 0; $attempt < 12; $attempt++) {
            try { return $this->createInTransaction($invoice, $data, $actorId); }
            catch (QueryException $e) {
                if (DB::getDriverName() !== 'sqlite' || !str_contains($e->getMessage(), 'database is locked') || $attempt === 11) throw $e;
                usleep(random_int(20000, 70000) * ($attempt + 1));
            }
        }
        throw new \LogicException('Payment creation retry limit reached.');
    }

    private function createInTransaction(Invoice $invoice, array $data, int $actorId): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $actorId) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $this->assertInvoiceAcceptsPayment($locked);
            $completed = $this->completedTotal($locked);
            $balance = bcsub((string) $locked->total_amount, $completed, 2);
            $amount = $this->money((string) $data['amount']);
            if (bccomp($amount, '0', 2) <= 0) throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            if (bccomp($amount, $balance, 2) > 0) throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the current invoice balance.']);
            $mode = $data['payment_mode'];
            if ($mode === 'upi' && empty($data['upi_reference']) && empty($data['transaction_reference'])) throw ValidationException::withMessages(['upi_reference' => 'UPI reference is required.']);
            if ($mode === 'bank_transfer' && empty($data['bank_reference']) && empty($data['transaction_reference'])) throw ValidationException::withMessages(['bank_reference' => 'Bank reference is required.']);
            if ($mode === 'cheque' && (empty($data['cheque_number']) || empty($data['cheque_date']))) throw ValidationException::withMessages(['cheque_number' => 'Cheque number and date are required.']);
            $year = (int) date('Y', strtotime($data['payment_date']));
            $code = $this->nextCode(PaymentSequence::class, $year, 'HH-PAY');
            $status = $data['status'] ?? PaymentRecordStatus::Completed->value;
            if ($status === PaymentRecordStatus::Completed->value && $mode === 'cheque' && !empty($data['hold_cheque'])) $status = PaymentRecordStatus::Pending->value;
            $payment = Payment::create(['payment_code' => $code, 'invoice_id' => $locked->id, 'customer_id' => $locked->customer_id, 'payment_date' => $data['payment_date'], 'amount' => $amount, 'payment_mode' => $mode, 'transaction_reference' => $data['transaction_reference'] ?? null, 'bank_reference' => $data['bank_reference'] ?? null, 'upi_reference' => $data['upi_reference'] ?? null, 'cheque_number' => $data['cheque_number'] ?? null, 'cheque_date' => $data['cheque_date'] ?? null, 'received_by' => $data['received_by'] ?? $actorId, 'status' => $status, 'notes' => $data['notes'] ?? null, 'created_by' => $actorId, 'updated_by' => $actorId]);
            $payment->statusHistory()->create(['new_status' => $status, 'changed_by' => $actorId, 'reason' => 'Payment recorded']);
            if ($status === PaymentRecordStatus::Completed->value) { $this->reconcileLockedInvoice($locked, $actorId, 'Payment completed'); $this->createReceipt($payment, $completed, $actorId); }
            return $payment->fresh(['invoice','receipt']);
        }, 5);
    }

    public function completePayment(Payment $payment, int $actorId, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $actorId, $reason) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status !== PaymentRecordStatus::Pending) throw ValidationException::withMessages(['status' => 'Only pending payments can be completed.']);
            $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail(); $this->assertInvoiceAcceptsPayment($invoice);
            $completed = $this->completedTotal($invoice); $balance = bcsub((string) $invoice->total_amount, $completed, 2);
            if (bccomp((string) $payment->amount, $balance, 2) > 0) throw ValidationException::withMessages(['amount' => 'Payment now exceeds the invoice balance.']);
            $payment->update(['status' => PaymentRecordStatus::Completed, 'updated_by' => $actorId]); $payment->statusHistory()->create(['old_status' => 'pending', 'new_status' => 'completed', 'changed_by' => $actorId, 'reason' => $reason ?: 'Payment completed']);
            $this->reconcileLockedInvoice($invoice, $actorId, 'Payment completed'); $this->createReceipt($payment, $completed, $actorId);
            return $payment->fresh(['invoice','receipt']);
        });
    }

    public function cancelPayment(Payment $payment, int $actorId, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $actorId, $reason) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (in_array($payment->status, [PaymentRecordStatus::Cancelled, PaymentRecordStatus::Failed, PaymentRecordStatus::Refunded], true)) throw ValidationException::withMessages(['status' => 'This payment is already closed.']);
            $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail(); $old = $payment->status->value;
            $payment->update(['status' => PaymentRecordStatus::Cancelled, 'updated_by' => $actorId]); $payment->statusHistory()->create(['old_status' => $old, 'new_status' => 'cancelled', 'changed_by' => $actorId, 'reason' => $reason]);
            $this->reconcileLockedInvoice($invoice, $actorId, 'Payment cancelled'); return $payment->fresh(['invoice','receipt']);
        });
    }

    public function reconcileInvoice(Invoice $invoice, int $actorId, ?string $reason = null): Invoice
    { return DB::transaction(function () use ($invoice, $actorId, $reason) { $invoice=Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail(); return $this->reconcileLockedInvoice($invoice,$actorId,$reason); }); }

    private function reconcileLockedInvoice(Invoice $invoice, int $actorId, string $reason): Invoice
    {
        $paid = $this->completedTotal($invoice); $balance = bcsub((string) $invoice->total_amount, $paid, 2); if (bccomp($balance,'0',2)<0) throw ValidationException::withMessages(['amount'=>'Completed payments exceed invoice total.']);
        $new = bccomp($paid,'0',2)===0 ? PaymentStatus::Unpaid : (bccomp($balance,'0',2)===0 ? PaymentStatus::Paid : PaymentStatus::PartiallyPaid); $old=$invoice->payment_status->value;
        $invoice->update(['paid_amount'=>$paid,'balance_amount'=>$balance,'payment_status'=>$new,'updated_by'=>$actorId]);
        if ($old !== $new->value) $invoice->paymentStatusHistory()->create(['old_status'=>$old,'new_status'=>$new->value,'paid_amount'=>$paid,'balance_amount'=>$balance,'changed_by'=>$actorId,'reason'=>$reason]);
        return $invoice->fresh();
    }

    private function createReceipt(Payment $payment, string $previouslyPaid, int $actorId): PaymentReceipt
    {
        $payment->load(['invoice','customer']); $invoice=$payment->invoice; $agency=AgencySetting::firstOrCreate([],['business_name'=>'Helper Home']); $year=(int)date('Y',strtotime($payment->payment_date)); $number=$this->nextCode(ReceiptSequence::class,$year,'HH-RCP'); $paid=bcadd($previouslyPaid,(string)$payment->amount,2); $balance=bcsub((string)$invoice->total_amount,$paid,2);
        $address=collect([$invoice->customer_address_snapshot])->filter()->join(', '); return PaymentReceipt::create(['receipt_number'=>$number,'payment_id'=>$payment->id,'invoice_id'=>$invoice->id,'customer_id'=>$invoice->customer_id,'receipt_date'=>$payment->payment_date,'invoice_total_snapshot'=>$invoice->total_amount,'previously_paid_snapshot'=>$previouslyPaid,'payment_amount_snapshot'=>$payment->amount,'balance_amount_snapshot'=>$balance,'payment_mode_snapshot'=>$payment->payment_mode->value,'transaction_reference_snapshot'=>$payment->transaction_reference ?: $payment->upi_reference ?: $payment->bank_reference ?: $payment->cheque_number,'customer_name_snapshot'=>$invoice->customer_name_snapshot,'customer_mobile_snapshot'=>$invoice->customer_mobile_snapshot,'customer_address_snapshot'=>$address,'customer_email_snapshot'=>$invoice->customer_email_snapshot,'agency_name_snapshot'=>$invoice->agency_name_snapshot ?: $agency->business_name,'agency_address_snapshot'=>$invoice->agency_address_snapshot,'agency_phone_snapshot'=>$invoice->agency_phone_snapshot ?: $agency->phone_primary,'agency_email_snapshot'=>$invoice->agency_email_snapshot ?: $agency->email,'agency_gst_snapshot'=>$invoice->agency_gst_snapshot ?: $agency->gst_number,'bank_holder_snapshot'=>$invoice->bank_holder_snapshot,'bank_name_snapshot'=>$invoice->bank_name_snapshot,'bank_ifsc_snapshot'=>$invoice->bank_ifsc_snapshot,'upi_id_snapshot'=>$invoice->upi_id_snapshot,'authorized_person_snapshot'=>$agency->owner_authorized_person,'signature_path_snapshot'=>$invoice->signature_path_snapshot,'stamp_path_snapshot'=>$invoice->stamp_path_snapshot,'notes_snapshot'=>$payment->notes,'created_by'=>$actorId]);
    }

    private function completedTotal(Invoice $invoice): string { return $this->money((string) Payment::where('invoice_id',$invoice->id)->where('status',PaymentRecordStatus::Completed->value)->sum('amount')); }
    private function nextCode(string $model, int $year, string $prefix): string { $model::query()->insertOrIgnore(['year'=>$year,'last_number'=>0,'created_at'=>now(),'updated_at'=>now()]); $seq=$model::whereKey($year)->lockForUpdate()->firstOrFail(); $seq->increment('last_number'); return sprintf('%s-%d-%06d',$prefix,$year,$seq->last_number); }
    private function assertInvoiceAcceptsPayment(Invoice $invoice): void { if ($invoice->status===InvoiceStatus::Cancelled) throw ValidationException::withMessages(['invoice'=>'Cancelled invoices cannot accept payments.']); if (bccomp((string)$invoice->total_amount,'0',2)<=0) throw ValidationException::withMessages(['invoice'=>'Invoice has no payable balance.']); }
    private function money(string $value): string { return bcadd($value, bccomp($value,'0',4)<0?'-0.005':'0.005', 2); }
}
