<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\GenerateInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\UpdateInvoiceStatusRequest;
use App\Models\AgencySetting;
use App\Models\Agreement;
use App\Models\Assignment;
use App\Models\Customer;
use App\Models\CustomerRequirement;
use App\Models\DocumentShareLink;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\ActivityLogger;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Invoice::with(['customer', 'assignment.service', 'agreement', 'items.service']);
        if ($request->filled('search')) {
            $term = '%'.trim($request->input('search')).'%';
            $query->where(fn($q) => $q->where('invoice_number', 'like', $term)
                ->orWhereHas('customer', fn($c) => $c->where('name', 'like', $term)->orWhere('mobile_number', 'like', $term))
                ->orWhereHas('assignment', fn($a) => $a->where('assignment_code', 'like', $term))
                ->orWhereHas('agreement', fn($a) => $a->where('agreement_code', 'like', $term)));
        }
        foreach (['status', 'customer_id'] as $field) if ($request->filled($field)) $query->where($field, $request->input($field));
        if ($request->filled('payment_status')) {
            $query->where('status', '!=', 'cancelled');
            if ($request->input('payment_status') === 'overdue') $query->where('payment_status', '!=', 'paid')->whereDate('due_date', '<', today())->where('balance_amount', '>', 0);
            elseif ($request->input('payment_status') === 'unpaid') $query->whereIn('payment_status', ['unpaid', 'partially_paid', 'overdue'])->where('balance_amount', '>', 0);
            else $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->filled('date')) $query->whereDate('invoice_date', $request->input('date'));
        if ($request->filled('service_id')) $query->whereHas('items', fn($q) => $q->where('service_id', $request->input('service_id')));
        return view('admin.invoices.index', ['invoices' => $query->latest('invoice_date')->paginate(20)->withQueryString(),
            'customers' => Customer::orderBy('name')->get(['id', 'name']), 'services' => Service::orderBy('name')->get(['id', 'name'])]);
    }

    public function create(Request $request, ?Agreement $agreement = null, ?Assignment $assignment = null): View
    {
        $agreement ??= $request->filled('agreement') ? Agreement::findOrFail($request->integer('agreement')) : null;
        $assignment ??= $agreement?->assignment ?? ($request->filled('assignment') ? Assignment::findOrFail($request->integer('assignment')) : null);
        $customer = $agreement?->customer ?? $assignment?->customer;
        $items = [];
        $salary = $agreement?->monthly_salary ?? $assignment?->monthly_salary;
        $charge = $agreement?->monthly_agency_service_charge ?? $assignment?->agency_service_charge;
        if ($salary !== null && (float) $salary > 0) $items[] = ['description' => 'Monthly Worker Salary', 'quantity' => 1, 'rate' => $salary, 'service_id' => $agreement?->service_id ?? $assignment?->service_id];
        if ($charge !== null) $items[] = ['description' => 'Monthly Agency Service Charge', 'quantity' => 1, 'rate' => $charge, 'service_id' => $agreement?->service_id ?? $assignment?->service_id];
        $invoice = new Invoice(['invoice_date' => today(), 'tax_type' => 'none', 'tax_rate' => 0, 'cgst_rate' => 0, 'sgst_rate' => 0, 'igst_rate' => 0, 'round_off' => 0]);
        $invoice->setRelation('items', collect($items ?: [['description' => '', 'quantity' => 1, 'rate' => 0]]));
        $agency = AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home']);
        return view('admin.invoices.form', $this->formData($invoice, $agency, $customer, $assignment, $agreement));
    }

    public function fromAgreement(Agreement $agreement, Request $request): View { return $this->create($request, $agreement); }
    public function fromAssignment(Assignment $assignment, Request $request): View { return $this->create($request, null, $assignment); }

    public function store(StoreInvoiceRequest $request, InvoiceService $service, ActivityLogger $logger): RedirectResponse
    {
        $invoice = $service->create($request->validated(), $request->user()->id);
        $logger->log('created', 'invoices', $invoice, 'Invoice created.', ['invoice_number' => $invoice->invoice_number]);
        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice draft created.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['items.service', 'customer', 'assignment.service', 'agreement', 'statusHistory.changedBy', 'paymentStatusHistory.changedBy', 'versions', 'payments.receipt', 'payments.receiver']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        abort_if($invoice->status === InvoiceStatus::Cancelled || $invoice->payment_status->value !== 'unpaid' || bccomp($invoice->paid_amount, '0', 2) > 0, 403);
        $invoice->load('items');
        return view('admin.invoices.form', $this->formData($invoice, AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home']), $invoice->customer, $invoice->assignment, $invoice->agreement));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $service, InvoicePdfService $pdf, ActivityLogger $logger): RedirectResponse
    {
        $needsPdf = $invoice->status !== InvoiceStatus::Draft;
        $invoice = DB::transaction(function () use ($service, $pdf, $invoice, $request, $needsPdf) {
            $updated = $service->update($invoice, $request->validated(), $request->user()->id);
            if ($needsPdf) $updated = $pdf->generate($updated, $request->user()->id);
            return $updated;
        });
        $logger->log('updated', 'invoices', $invoice, 'Invoice updated.');
        if ($needsPdf) $logger->log('pdf_regenerated', 'invoices', $invoice, 'Invoice PDF regenerated.');
        return redirect()->route('admin.invoices.show', $invoice)->with('success', $needsPdf ? 'Invoice updated and new PDF version generated.' : 'Invoice updated.');
    }

    public function preview(Invoice $invoice, InvoicePdfService $pdf): View
    {
        $invoice->load('items');
        return view('admin.invoices.preview', $pdf->documentData($invoice));
    }

    public function print(Invoice $invoice, InvoicePdfService $pdf): View { return $this->preview($invoice, $pdf); }

    public function generate(GenerateInvoiceRequest $request, Invoice $invoice, InvoicePdfService $pdf, ActivityLogger $logger): RedirectResponse
    {
        if ($invoice->pdf_version && !$request->boolean('confirm_regenerate')) return back()->withErrors(['confirm_regenerate' => 'Confirm PDF regeneration.']);
        $old = $invoice->pdf_version;
        $pdf->generate($invoice, $request->user()->id);
        $logger->log($old ? 'pdf_regenerated' : 'generated', 'invoices', $invoice, $old ? 'Invoice PDF regenerated.' : 'Invoice generated.');
        return back()->with('success', $old ? 'New PDF version generated.' : 'Invoice PDF generated.');
    }

    public function download(Invoice $invoice): mixed
    {
        abort_unless($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404);
        return Storage::disk('local')->download($invoice->pdf_path, $invoice->invoice_number.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function whatsapp(Request $request, Invoice $invoice, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($invoice->pdf_path && $invoice->status !== InvoiceStatus::Cancelled, 422);
        $link = DocumentShareLink::create(['token' => Str::random(64), 'document_type' => 'invoice', 'document_id' => $invoice->id,
            'expires_at' => now()->addDays(7), 'is_active' => true, 'created_by' => $request->user()->id]);
        $url = route('shared.invoices.download', $link->token);
        $message = "Hello {$invoice->customer_name_snapshot},\n\nYour Helper Home invoice {$invoice->invoice_number} is ready.\n\nTotal Amount: ₹{$invoice->total_amount}\n\nDownload: {$url}\n\nThank you,\nHelper Home";
        $phone = preg_replace('/\D+/', '', (string) $invoice->customer_mobile_snapshot);
        $logger->log('shared', 'invoices', $invoice, 'Invoice shared through expiring link.');
        return redirect()->away('https://wa.me/'.$phone.'?text='.rawurlencode($message));
    }

    public function markSent(UpdateInvoiceStatusRequest $request, Invoice $invoice, InvoiceService $service, ActivityLogger $logger): RedirectResponse
    {
        $service->transition($invoice, InvoiceStatus::Sent, $request->user()->id, $request->validated('reason') ?: 'Marked sent');
        $logger->log('marked_sent', 'invoices', $invoice, 'Invoice marked sent.');
        return back()->with('success', 'Invoice marked sent.');
    }

    public function cancel(CancelInvoiceRequest $request, Invoice $invoice, InvoiceService $service, ActivityLogger $logger): RedirectResponse
    {
        $service->transition($invoice, InvoiceStatus::Cancelled, $request->user()->id, $request->validated('reason'));
        $logger->log('cancelled', 'invoices', $invoice, 'Invoice cancelled.');
        return back()->with('success', 'Invoice cancelled.');
    }

    private function formData(Invoice $invoice, AgencySetting $agency, ?Customer $customer, ?Assignment $assignment, ?Agreement $agreement): array
    {
        return compact('invoice', 'agency', 'customer', 'assignment', 'agreement') + [
            'customers' => Customer::orderBy('name')->get(), 'assignments' => Assignment::with('customer:id,name')->latest()->get(),
            'agreements' => Agreement::with('customer:id,name')->latest()->get(), 'requirements' => CustomerRequirement::latest()->get(),
            'services' => Service::orderBy('name')->get(),
        ];
    }
}
