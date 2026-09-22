<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\AgencySetting;
use App\Models\ActivityLog;
use App\Models\Agreement;
use App\Models\Assignment;
use App\Models\Customer;
use App\Models\CustomerRequirement;
use App\Models\DocumentShareLink;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\Worker;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StageSixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    public function test_super_admin_creates_multiple_items_with_server_calculated_gst_and_snapshots(): void
    {
        $customer = $this->customer();
        AgencySetting::firstOrFail()->update(['bank_account_holder_name' => 'Helper Home Owner', 'bank_account_number' => '1234567890']);
        $data = $this->data($customer);
        $data['items'] = [['description' => 'Service A', 'quantity' => 2, 'rate' => 100], ['description' => 'Service B', 'quantity' => 1, 'rate' => 50]];
        $data += ['discount_type' => 'percentage', 'discount_value' => 10];
        $data['tax_type'] = 'intra_state'; $data['tax_rate'] = 18; $data['cgst_rate'] = 9; $data['sgst_rate'] = 9;
        $this->actingAs($this->admin())->post(route('admin.invoices.store'), $data + ['subtotal' => 1, 'total_amount' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $invoice = Invoice::firstOrFail();
        $this->assertMatchesRegularExpression('/^HH-INV-\d{4}-\d{6}$/', $invoice->invoice_number);
        $this->assertCount(2, $invoice->items);
        $this->assertSame('250.00', $invoice->subtotal);
        $this->assertSame('25.00', $invoice->discount_amount);
        $this->assertSame('20.25', $invoice->cgst_amount);
        $this->assertSame('20.25', $invoice->sgst_amount);
        $this->assertSame('265.50', $invoice->total_amount);
        $this->assertSame('200.00', $invoice->items[0]->amount);
        $customer->update(['name' => 'Changed']); AgencySetting::firstOrFail()->update(['business_name' => 'Changed Agency', 'bank_account_number' => '9999999999']);
        $this->assertSame('Test Customer', $invoice->fresh()->customer_name_snapshot);
        $this->assertSame('Helper Home', $invoice->fresh()->agency_name_snapshot);
        $this->assertSame('1234567890', $invoice->fresh()->bank_account_snapshot);
        $this->assertDatabaseHas('invoice_status_history', ['invoice_id' => $invoice->id, 'new_status' => 'draft']);
    }

    public function test_igst_no_tax_and_unique_sequence(): void
    {
        $customer = $this->customer(); $service = app(InvoiceService::class);
        $data = $this->data($customer); $data['tax_type'] = 'inter_state'; $data['tax_rate'] = 18; $data['igst_rate'] = 18;
        $first = $service->create($data, $this->admin()->id);
        $this->assertSame('118.00', $first->total_amount);
        $this->assertSame('18.00', $first->igst_amount);
        $data['tax_type'] = 'none'; $data['tax_rate'] = 0; $data['igst_rate'] = 0;
        $second = $service->create($data, $this->admin()->id);
        $this->assertSame('100.00', $second->total_amount);
        $this->assertNotSame($first->invoice_number, $second->invoice_number);
    }

    public function test_assignment_and_agreement_sources_are_linked_and_mismatch_is_rejected(): void
    {
        $customer = $this->customer(); $other = $this->customer('Other');
        $requirement = CustomerRequirement::create(['requirement_code' => 'REQ-1', 'customer_id' => $customer->id, 'service_id' => Service::firstOrFail()->id]);
        $worker = Worker::create(['worker_code' => 'WRK-1', 'registration_date' => today(), 'name' => 'Worker', 'mobile_number' => '9999999999', 'address_line_1' => 'Address', 'city' => 'Mumbai', 'state' => 'MH']);
        $assignment = Assignment::create(['assignment_code' => 'ASS-1', 'customer_id' => $customer->id, 'customer_requirement_id' => $requirement->id, 'worker_id' => $worker->id, 'service_id' => Service::firstOrFail()->id, 'assignment_start_date' => today(), 'monthly_salary' => 5000, 'agency_service_charge' => 200]);
        $agreement = Agreement::create(['agreement_code' => 'AGR-1', 'customer_id' => $customer->id, 'customer_requirement_id' => $requirement->id, 'assignment_id' => $assignment->id, 'agreement_date' => today()]);
        $this->actingAs($this->admin())->get(route('admin.assignments.invoice.create', $assignment))->assertOk()->assertSee('Monthly Agency Service Charge')->assertSee('Monthly Worker Salary');
        $this->actingAs($this->admin())->get(route('admin.agreements.invoice.create', $agreement))->assertOk();
        $data = $this->data($customer) + ['agreement_id' => $agreement->id];
        $invoice = app(InvoiceService::class)->create($data, $this->admin()->id);
        $this->assertSame($assignment->id, $invoice->assignment_id);
        $this->assertSame($requirement->id, $invoice->customer_requirement_id);
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->create($this->data($other) + ['agreement_id' => $agreement->id], $this->admin()->id);
    }

    public function test_private_pdf_download_share_history_and_cancel(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        app(InvoicePdfService::class)->generate($invoice, $this->admin()->id);
        $invoice = $invoice->fresh();
        $this->assertSame(InvoiceStatus::Generated, $invoice->status);
        Storage::disk('local')->assertExists($invoice->pdf_path);
        $this->actingAs($this->admin())->get(route('admin.invoices.download', $invoice))->assertOk();
        $this->actingAs($this->admin())->post(route('admin.invoices.share.whatsapp', $invoice))->assertRedirect();
        $link = DocumentShareLink::where('document_type', 'invoice')->firstOrFail();
        $this->get(route('shared.invoices.download', $link->token))->assertOk();
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->firstOrFail()->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('admin.invoices.download', $invoice))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.invoices.store'), $this->data($invoice->customer))->assertForbidden();
        app(InvoiceService::class)->transition($invoice, InvoiceStatus::Cancelled, $this->admin()->id, 'Correction');
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'cancelled']);
        $this->get(route('shared.invoices.download', $link->token))->assertGone();
    }

    public function test_paid_invoice_cannot_be_edited_and_dashboard_uses_real_amounts(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertSee('Total Invoices')->assertSee('100.00');
        $invoice->update(['payment_status' => 'paid', 'paid_amount' => 100, 'balance_amount' => 0]);
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->update($invoice, $this->data($invoice->customer), $this->admin()->id);
    }

    public function test_invoice_pages_render_and_regeneration_preserves_prior_pdf(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $admin = $this->admin();
        foreach ([route('admin.invoices.index'), route('admin.invoices.create'), route('admin.invoices.show', $invoice),
            route('admin.invoices.edit', $invoice), route('admin.invoices.preview', $invoice), route('admin.invoices.print', $invoice),
            route('admin.customers.show', $invoice->customer)] as $url) $this->actingAs($admin)->get($url)->assertOk();
        $pdf = app(InvoicePdfService::class);
        $pdf->generate($invoice, $admin->id);
        $first = $invoice->fresh()->pdf_path;
        $pdf->generate($invoice, $admin->id);
        $this->assertSame(2, $invoice->fresh()->pdf_version);
        $this->assertDatabaseCount('invoice_versions', 2);
        Storage::disk('local')->assertExists($first);
    }

    public function test_fixed_discount_and_round_off_are_calculated_on_server(): void
    {
        $data = $this->data($this->customer());
        $data += ['discount_type' => 'fixed', 'discount_value' => 12.50, 'round_off' => -0.25];
        $invoice = app(InvoiceService::class)->create($data, $this->admin()->id);
        $this->assertSame('12.50', $invoice->discount_amount);
        $this->assertSame('87.25', $invoice->total_amount);
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $data = $this->data($this->customer()) + ['discount_type' => 'fixed', 'discount_value' => 101];
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->create($data, $this->admin()->id);
    }

    public function test_cgst_and_sgst_must_equal_total_rate(): void
    {
        $data = $this->data($this->customer());
        $data['tax_type'] = 'intra_state'; $data['tax_rate'] = 18; $data['cgst_rate'] = 9; $data['sgst_rate'] = 8;
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->create($data, $this->admin()->id);
    }

    public function test_igst_cannot_be_combined_with_cgst(): void
    {
        $data = $this->data($this->customer());
        $data['tax_type'] = 'inter_state'; $data['tax_rate'] = 18; $data['igst_rate'] = 18; $data['cgst_rate'] = 9;
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->create($data, $this->admin()->id);
    }

    public function test_cancel_requires_reason_and_preserves_invoice(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $this->actingAs($this->admin())->post(route('admin.invoices.cancel', $invoice))->assertSessionHasErrors('reason');
        $this->assertSame(InvoiceStatus::Draft, $invoice->fresh()->status);
        $this->actingAs($this->admin())->post(route('admin.invoices.cancel', $invoice), ['reason' => 'Entered in error'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoice_status_history', ['invoice_id' => $invoice->id, 'new_status' => 'cancelled', 'reason' => 'Entered in error']);
    }

    public function test_generated_invoice_edit_requires_confirmed_new_version(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        app(InvoicePdfService::class)->generate($invoice, $this->admin()->id);
        $data = $this->data($invoice->customer); $data['items'][0]['rate'] = 200;
        $this->actingAs($this->admin())->put(route('admin.invoices.update', $invoice), $data)->assertSessionHasErrors('confirm_regenerate');
        $this->assertSame('100.00', $invoice->fresh()->total_amount);
        $this->actingAs($this->admin())->put(route('admin.invoices.update', $invoice), $data + ['confirm_regenerate' => 1])->assertSessionHasNoErrors();
        $this->assertSame('200.00', $invoice->fresh()->total_amount);
        $this->assertSame(2, $invoice->fresh()->pdf_version);
    }

    public function test_sent_invoice_needs_pdf_and_status_history(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $this->actingAs($this->admin())->post(route('admin.invoices.mark-sent', $invoice))->assertSessionHasErrors('status');
        app(InvoicePdfService::class)->generate($invoice, $this->admin()->id);
        $this->actingAs($this->admin())->post(route('admin.invoices.mark-sent', $invoice))->assertSessionHasNoErrors();
        $this->assertSame(InvoiceStatus::Sent, $invoice->fresh()->status);
        $this->assertDatabaseHas('invoice_status_history', ['invoice_id' => $invoice->id, 'new_status' => 'sent']);
    }

    public function test_overdue_is_derived_without_rewriting_payment_status(): void
    {
        $data = $this->data($this->customer()); $data['invoice_date'] = today()->subDays(10)->format('Y-m-d'); $data['due_date'] = today()->subDay()->format('Y-m-d');
        $invoice = app(InvoiceService::class)->create($data, $this->admin()->id);
        $this->assertSame('overdue', $invoice->effectivePaymentStatus()->value);
        $this->assertSame('unpaid', $invoice->payment_status->value);
        $this->actingAs($this->admin())->get(route('admin.invoices.index', ['payment_status' => 'overdue']))->assertSee($invoice->invoice_number);
    }

    public function test_expired_invoice_share_link_is_rejected(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        app(InvoicePdfService::class)->generate($invoice, $this->admin()->id);
        $link = DocumentShareLink::create(['token' => str_repeat('b', 64), 'document_type' => 'invoice', 'document_id' => $invoice->id, 'expires_at' => now()->subMinute(), 'is_active' => true]);
        $this->get(route('shared.invoices.download', $link->token))->assertGone();
    }

    public function test_invoice_number_is_immutable(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $number = $invoice->invoice_number;
        $invoice->update(['invoice_number' => 'ALTERED']);
        $this->assertSame($number, $invoice->fresh()->invoice_number);
    }

    public function test_quantity_times_rate_rounds_to_paise(): void
    {
        $data = $this->data($this->customer()); $data['items'] = [['description' => 'Fractional work', 'quantity' => 0.33, 'rate' => 10.01]];
        $invoice = app(InvoiceService::class)->create($data, $this->admin()->id);
        $this->assertSame('3.30', $invoice->items->first()->amount);
        $this->assertSame($invoice->items->first()->amount, $invoice->subtotal);
    }

    public function test_invoice_assets_remain_after_agency_asset_changes(): void
    {
        Storage::fake('public'); Storage::disk('public')->put('agency/upi/original.png', 'image bytes');
        AgencySetting::firstOrFail()->update(['upi_qr_path' => 'agency/upi/original.png']);
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        Storage::disk('public')->delete('agency/upi/original.png');
        $this->assertNotNull($invoice->upi_qr_snapshot);
        Storage::disk('local')->assertExists($invoice->upi_qr_snapshot);
    }

    public function test_requirement_from_another_customer_is_rejected(): void
    {
        $customer = $this->customer(); $other = $this->customer('Other');
        $requirement = CustomerRequirement::create(['requirement_code' => 'REQ-OTHER', 'customer_id' => $other->id, 'service_id' => Service::firstOrFail()->id]);
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->create($this->data($customer) + ['customer_requirement_id' => $requirement->id], $this->admin()->id);
    }

    public function test_assignment_from_another_customer_is_rejected(): void
    {
        $customer = $this->customer(); $other = $this->customer('Other');
        $requirement = CustomerRequirement::create(['requirement_code' => 'REQ-OTHER', 'customer_id' => $other->id, 'service_id' => Service::firstOrFail()->id]);
        $worker = Worker::create(['worker_code' => 'WRK-OTHER', 'registration_date' => today(), 'name' => 'Worker', 'mobile_number' => '9999999999', 'address_line_1' => 'Address', 'city' => 'Mumbai', 'state' => 'MH']);
        $assignment = Assignment::create(['assignment_code' => 'ASS-OTHER', 'customer_id' => $other->id, 'customer_requirement_id' => $requirement->id, 'worker_id' => $worker->id, 'service_id' => Service::firstOrFail()->id, 'assignment_start_date' => today()]);
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->create($this->data($customer) + ['assignment_id' => $assignment->id], $this->admin()->id);
    }

    public function test_unauthorized_staff_cannot_view_or_print_invoice(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->firstOrFail()->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('admin.invoices.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.invoices.show', $invoice))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.invoices.print', $invoice))->assertForbidden();
    }

    public function test_paid_invoice_cannot_be_cancelled(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $invoice->update(['payment_status' => 'paid', 'paid_amount' => 100, 'balance_amount' => 0]);
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->transition($invoice, InvoiceStatus::Cancelled, $this->admin()->id, 'Void');
    }

    public function test_editing_customer_snapshot_does_not_change_customer_master(): void
    {
        $customer = $this->customer(); $invoice = app(InvoiceService::class)->create($this->data($customer), $this->admin()->id);
        $data = $this->data($customer) + ['customer_name_snapshot' => 'Billing Contact', 'customer_address_snapshot' => 'Billing Address'];
        app(InvoiceService::class)->update($invoice, $data, $this->admin()->id);
        $this->assertSame('Billing Contact', $invoice->fresh()->customer_name_snapshot);
        $this->assertSame('Test Customer', $customer->fresh()->name);
        $this->assertSame('Original Address', $customer->fresh()->address_line_1);
    }

    public function test_whatsapp_message_uses_secure_link_and_invoice_total(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        app(InvoicePdfService::class)->generate($invoice, $this->admin()->id);
        $response = $this->actingAs($this->admin())->post(route('admin.invoices.share.whatsapp', $invoice));
        $location = rawurldecode($response->headers->get('Location'));
        $this->assertStringContainsString($invoice->invoice_number, $location);
        $this->assertStringContainsString('100.00', $location);
        $this->assertStringContainsString('/shared/invoice/', $location);
        $this->assertStringNotContainsString('storage/app/', $location);
    }

    public function test_due_date_before_invoice_date_is_rejected(): void
    {
        $data = $this->data($this->customer()); $data['due_date'] = today()->subDay()->format('Y-m-d');
        $this->actingAs($this->admin())->post(route('admin.invoices.store'), $data)->assertSessionHasErrors('due_date');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_dashboard_paid_amount_uses_recorded_invoice_field(): void
    {
        $invoice = app(InvoiceService::class)->create($this->data($this->customer()), $this->admin()->id);
        $invoice->update(['paid_amount' => 40, 'balance_amount' => 60, 'payment_status' => 'partially_paid']);
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertSee('Paid Amount')->assertSee('40.00');
    }

    public function test_invoice_actions_write_activity_without_bank_account_metadata(): void
    {
        AgencySetting::firstOrFail()->update(['bank_account_number' => '1234567890']);
        $this->actingAs($this->admin())->post(route('admin.invoices.store'), $this->data($this->customer()))->assertSessionHasNoErrors();
        $invoice = Invoice::firstOrFail();
        $this->actingAs($this->admin())->post(route('admin.invoices.generate', $invoice))->assertSessionHasNoErrors();
        $this->actingAs($this->admin())->post(route('admin.invoices.mark-sent', $invoice))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['module' => 'invoices', 'action' => 'created', 'subject_id' => $invoice->id]);
        $this->assertDatabaseHas('activity_logs', ['module' => 'invoices', 'action' => 'generated', 'subject_id' => $invoice->id]);
        $this->assertDatabaseHas('activity_logs', ['module' => 'invoices', 'action' => 'marked_sent', 'subject_id' => $invoice->id]);
        $this->assertStringNotContainsString('1234567890', ActivityLog::where('module', 'invoices')->get()->toJson());
    }

    private function admin(): User { return User::whereHas('role', fn($q) => $q->where('slug', 'super-admin'))->firstOrFail(); }
    private function customer(string $name = 'Test Customer'): Customer { return Customer::create(['customer_code' => 'C-'.uniqid(), 'registration_date' => today(), 'name' => $name, 'mobile_number' => '9876543210', 'address_line_1' => 'Original Address']); }
    private function data(Customer $customer): array { return ['customer_id' => $customer->id, 'invoice_date' => today()->format('Y-m-d'), 'items' => [['description' => 'Service', 'quantity' => 1, 'rate' => 100]], 'tax_type' => 'none', 'tax_rate' => 0]; }
}
