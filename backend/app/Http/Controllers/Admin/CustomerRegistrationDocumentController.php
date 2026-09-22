<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerGeneratedDocumentStatus;
use App\Enums\CustomerGeneratedDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateCustomerRegistrationFormRequest;
use App\Http\Requests\ShareCustomerGeneratedDocumentRequest;
use App\Models\Customer;
use App\Models\CustomerGeneratedDocument;
use App\Models\CustomerRequirement;
use App\Models\DocumentShareLink;
use App\Services\ActivityLogger;
use App\Services\CustomerDocumentGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerRegistrationDocumentController extends Controller
{
    public function customerPreview(Request $request, Customer $customer, CustomerDocumentGenerationService $service): View
    {
        $requirement = $this->resolveRequirement($customer, $request);
        return $this->previewRequirement($requirement, $service);
    }

    public function requirementPreview(CustomerRequirement $requirement, CustomerDocumentGenerationService $service): View
    {
        return $this->previewRequirement($requirement, $service);
    }

    public function generate(GenerateCustomerRegistrationFormRequest $request, CustomerRequirement $requirement, CustomerDocumentGenerationService $service, ActivityLogger $logger): RedirectResponse
    {
        $document = $service->generateRegistrationForm($requirement, $request->user()->id);
        $logger->log('generated', 'customer-registration-documents', $document, 'Customer registration form generated.', ['version' => $document->version_number]);
        return redirect()->route('admin.customer-generated-documents.show', $document)->with('success', 'Customer registration form generated successfully.');
    }

    public function show(CustomerGeneratedDocument $document): View
    {
        $document->load(['customer', 'requirement', 'generator']);
        return view('admin.customer-generated-documents.show', compact('document'));
    }

    public function preview(CustomerGeneratedDocument $document, CustomerDocumentGenerationService $service): View
    {
        return view('admin.customer-generated-documents.preview', $service->snapshotData($document) + ['document' => $document, 'previewDocument' => true]);
    }

    public function print(CustomerGeneratedDocument $document, CustomerDocumentGenerationService $service): View
    {
        abort_unless(request()->user()?->hasPermission('customer-registration-documents.print'), 403);
        return view('admin.customer-generated-documents.print', $service->snapshotData($document) + ['document' => $document, 'printMode' => true]);
    }

    public function download(CustomerGeneratedDocument $document, ActivityLogger $logger): mixed
    {
        abort_unless(request()->user()?->hasPermission('customer-registration-documents.download'), 403);
        abort_unless($document->pdf_path && Storage::disk('local')->exists($document->pdf_path), 404);
        $logger->log('downloaded', 'customer-registration-documents', $document, 'Customer registration form downloaded.');
        return Storage::disk('local')->download($document->pdf_path, ($document->document_number ?: 'customer-registration').'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function whatsapp(ShareCustomerGeneratedDocumentRequest $request, CustomerGeneratedDocument $document, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($document->pdf_path && Storage::disk('local')->exists($document->pdf_path), 422, 'Generate the document PDF first.');
        $link = DocumentShareLink::create(['token' => Str::random(64), 'document_type' => 'customer_generated_document', 'document_id' => $document->id, 'expires_at' => now()->addDays(7), 'is_active' => true, 'created_by' => $request->user()->id]);
        $document->update(['status' => CustomerGeneratedDocumentStatus::Shared, 'shared_at' => now()]);
        $snapshot = $document->snapshot_json;
        $customer = $snapshot['customer'] ?? [];
        $requirement = $snapshot['requirement'] ?? [];
        $url = route('shared.customer-generated-documents.download', $link->token);
        $message = "Hello {$customer['name']},\n\nPlease find your Helper Home Customer Registration Form for:\n\nService: ".($requirement['service_name'] ?? '—')."\nRequirement ID: ".($requirement['requirement_code'] ?? '—')."\n\nDocument: {$url}\n\nThank you,\nHelper Home";
        $phone = preg_replace('/\D+/', '', (string) ($customer['mobile_number'] ?? ''));
        $logger->log('shared', 'customer-registration-documents', $document, 'Customer registration form shared through an expiring link.');
        return redirect()->away('https://wa.me/'.$phone.'?text='.rawurlencode($message));
    }

    public function archive(Request $request, CustomerGeneratedDocument $document, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('customer-registration-documents.view'), 403);
        $document->update(['status' => CustomerGeneratedDocumentStatus::Archived]);
        $logger->log('archived', 'customer-registration-documents', $document, 'Customer registration form archived.');
        return back()->with('success', 'Customer registration form archived.');
    }

    private function previewRequirement(CustomerRequirement $requirement, CustomerDocumentGenerationService $service): View
    {
        $requirement->load(['customer', 'service', 'dutyType', 'householdDetail', 'accommodationDetail', 'workingCondition', 'workerPreference']);
        $snapshot = $service->buildRegistrationSnapshot($requirement);
        $document = new CustomerGeneratedDocument(['customer_id' => $requirement->customer_id, 'customer_requirement_id' => $requirement->id, 'document_type' => CustomerGeneratedDocumentType::RegistrationForm, 'version_number' => 0]);
        return view('admin.customers.documents.registration-preview', $service->templateData($document, $snapshot) + ['document' => $document, 'snapshot' => $snapshot, 'requirement' => $requirement, 'previewDocument' => false]);
    }

    private function resolveRequirement(Customer $customer, Request $request): CustomerRequirement
    {
        if ($request->filled('requirement')) return $customer->requirements()->whereKey($request->integer('requirement'))->firstOrFail();
        $requirements = $customer->requirements()->latest()->get();
        abort_if($requirements->count() !== 1, 422, 'Select a customer requirement before generating the registration form.');
        return $requirements->first();
    }
}
