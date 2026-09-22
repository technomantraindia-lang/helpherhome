<?php

namespace Tests\Feature;

use App\Enums\PaymentRecordStatus;
use App\Models\Customer;
use App\Models\DocumentShareLink;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\Role;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentReceiptPdfService;
use App\Services\PaymentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StageSevenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    public function test_admin_records_full_payment_and_receipt_reconciles_invoice(): void
    {
        $invoice = $this->invoice(30000);
        $payment = app(PaymentService::class)->createPayment($invoice, [
            'payment_date' => today()->format('Y-m-d'), 'amount' => 30000, 'payment_mode' => 'cash',
        ], $this->admin()->id);

        $invoice = $invoice->fresh();
        $this->assertMatchesRegularExpression('/^HH-PAY-\d{4}-\d{6}$/', $payment->payment_code);
        $this->assertSame(PaymentRecordStatus::Completed, $payment->status);
        $this->assertSame('30000.00', $invoice->paid_amount);
        $this->assertSame('0.00', $invoice->balance_amount);
        $this->assertSame('paid', $invoice->payment_status->value);
        $this->assertNotNull($payment->receipt);
        $this->assertMatchesRegularExpression('/^HH-RCP-\d{4}-\d{6}$/', $payment->receipt->receipt_number);
        $this->assertSame('0.00', $payment->receipt->balance_amount_snapshot);
    }

    public function test_partial_and_multiple_payments_create_individual_receipts(): void
    {
        $invoice = $this->invoice(30000);
        $service = app(PaymentService::class);
        $service->createPayment($invoice, ['payment_date' => today(), 'amount' => 10000, 'payment_mode' => 'upi', 'upi_reference' => 'UPI-1'], $this->admin()->id);
        $second = $service->createPayment($invoice, ['payment_date' => today(), 'amount' => 5000, 'payment_mode' => 'bank_transfer', 'bank_reference' => 'UTR-2'], $this->admin()->id);
        $third = $service->createPayment($invoice, ['payment_date' => today(), 'amount' => 15000, 'payment_mode' => 'cheque', 'cheque_number' => 'CH-3', 'cheque_date' => today()], $this->admin()->id);

        $invoice = $invoice->fresh();
        $this->assertSame('30000.00', $invoice->paid_amount);
        $this->assertSame('paid', $invoice->payment_status->value);
        $this->assertCount(3, PaymentReceipt::where('invoice_id', $invoice->id)->get());
        $this->assertSame('10000.00', $second->receipt->previously_paid_snapshot);
        $this->assertSame('15000.00', $third->receipt->previously_paid_snapshot);
    }

    public function test_pending_payment_does_not_reduce_balance_until_completed(): void
    {
        $invoice = $this->invoice(1000);
        $service = app(PaymentService::class);
        $payment = $service->createPayment($invoice, ['payment_date' => today(), 'amount' => 400, 'payment_mode' => 'cheque', 'cheque_number' => 'C1', 'cheque_date' => today(), 'status' => 'pending'], $this->admin()->id);
        $this->assertSame('0.00', $invoice->fresh()->paid_amount);
        $this->assertNull($payment->receipt);
        $service->completePayment($payment, $this->admin()->id);
        $this->assertSame('400.00', $invoice->fresh()->paid_amount);
    }

    public function test_overpayment_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->createPayment($this->invoice(100), ['payment_date' => today(), 'amount' => 101, 'payment_mode' => 'cash'], $this->admin()->id);
    }

    public function test_cancel_completed_payment_recalculates_from_remaining_completed_payments(): void
    {
        $invoice = $this->invoice(1000);
        $service = app(PaymentService::class);
        $first = $service->createPayment($invoice, ['payment_date' => today(), 'amount' => 400, 'payment_mode' => 'cash'], $this->admin()->id);
        $service->createPayment($invoice, ['payment_date' => today(), 'amount' => 200, 'payment_mode' => 'other', 'transaction_reference' => 'O'], $this->admin()->id);
        $service->cancelPayment($first, $this->admin()->id, 'Duplicate');
        $this->assertSame('200.00', $invoice->fresh()->paid_amount);
        $this->assertSame('800.00', $invoice->fresh()->balance_amount);
        $this->assertSame('cancelled', $first->fresh()->status->value);
        $this->assertNotNull($first->fresh()->receipt);
    }

    public function test_receipt_pdf_download_and_whatsapp_share_are_private(): void
    {
        $invoice = $this->invoice(1000);
        $payment = app(PaymentService::class)->createPayment($invoice, ['payment_date' => today(), 'amount' => 250, 'payment_mode' => 'other', 'transaction_reference' => 'REF'], $this->admin()->id);
        $receipt = $payment->receipt;
        app(PaymentReceiptPdfService::class)->generate($receipt, $this->admin()->id);
        $receipt = $receipt->fresh();
        Storage::disk('local')->assertExists($receipt->pdf_path);
        $this->actingAs($this->admin())->get(route('admin.payment-receipts.download', $receipt))->assertOk();
        $this->actingAs($this->admin())->post(route('admin.payment-receipts.share.whatsapp', $receipt))->assertRedirect();
        $link = DocumentShareLink::where('document_type', 'payment_receipt')->firstOrFail();
        $this->get(route('shared.payment-receipts.download', $link->token))->assertOk();
    }

    public function test_staff_without_permissions_cannot_record_or_view_payment(): void
    {
        $invoice = $this->invoice(100);
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->firstOrFail()->id, 'is_active' => true]);
        $this->actingAs($staff)->post(route('admin.invoices.payments.store', $invoice), ['payment_date' => today()->format('Y-m-d'), 'amount' => 10, 'payment_mode' => 'cash'])->assertForbidden();
        $this->actingAs($staff)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_payment_pages_and_dashboard_render(): void
    {
        $invoice = $this->invoice(100);
        $this->actingAs($this->admin())->get(route('admin.invoices.payments.create', $invoice))->assertOk()->assertSee('Receive Payment');
        $this->actingAs($this->admin())->get(route('admin.payments.index'))->assertOk()->assertSee('Payments');
        $this->actingAs($this->admin())->get(route('admin.payment-receipts.index'))->assertOk()->assertSee('Payment Receipts');
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertSee('Payments Received Today');
    }

    private function invoice(float $amount): Invoice
    {
        $customer = Customer::create(['customer_code' => 'CUS-'.uniqid(), 'registration_date' => today(), 'name' => 'Payment Customer', 'mobile_number' => '9876543210']);
        return app(InvoiceService::class)->create(['customer_id' => $customer->id, 'invoice_date' => today()->format('Y-m-d'), 'items' => [['description' => 'Service', 'quantity' => 1, 'rate' => $amount]], 'tax_type' => 'none', 'tax_rate' => 0], $this->admin()->id);
    }

    private function admin(): User
    {
        return User::whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))->firstOrFail();
    }
}
