<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkerGeneratedDocumentStatus;
use App\Enums\WorkerGeneratedDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ArchiveWorkerGeneratedDocumentRequest;
use App\Http\Requests\GenerateWorkerRegistrationFormRequest;
use App\Http\Requests\GenerateWorkerResumeRequest;
use App\Http\Requests\ShareWorkerGeneratedDocumentRequest;
use App\Models\DocumentShareLink;
use App\Models\Worker;
use App\Models\WorkerGeneratedDocument;
use App\Services\ActivityLogger;
use App\Services\WorkerDocumentGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkerGeneratedDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $documents = WorkerGeneratedDocument::with(['worker:id,worker_code,name', 'generator:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q->where('document_number', 'like', $term)
                    ->orWhereHas('worker', fn ($worker) => $worker->where('name', 'like', $term)->orWhere('worker_code', 'like', $term)));
            })
            ->when($request->filled('document_type'), fn ($query) => $query->where('document_type', $request->input('document_type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('generated_at', $request->input('date')))
            ->latest('generated_at')->paginate(25)->withQueryString();

        return view('admin.worker-generated-documents.index', [
            'documents' => $documents,
            'types' => WorkerGeneratedDocumentType::cases(),
            'statuses' => WorkerGeneratedDocumentStatus::cases(),
        ]);
    }

    public function resumePreview(Worker $worker, WorkerDocumentGenerationService $service): View
    {
        $worker->load(['services', 'preferredDutyType', 'verification', 'interviews.service', 'interviews.preferredDutyType', 'references']);
        return view('admin.worker-generated-documents.preview', $this->previewData($worker, WorkerGeneratedDocumentType::Resume, $service));
    }

    public function registrationPreview(Worker $worker, WorkerDocumentGenerationService $service): View
    {
        $worker->load(['services', 'preferredDutyType', 'verification', 'references', 'documents', 'supervisor', 'executive']);
        return view('admin.worker-generated-documents.preview', $this->previewData($worker, WorkerGeneratedDocumentType::RegistrationForm, $service));
    }

    public function generateResume(GenerateWorkerResumeRequest $request, Worker $worker, WorkerDocumentGenerationService $service, ActivityLogger $logger): RedirectResponse
    {
        $document = $service->generateResume($worker, $request->user()->id);
        $logger->log('generated', 'worker-resumes', $document, 'Worker resume generated.', ['version' => $document->version_number]);
        return redirect()->route('admin.worker-generated-documents.show', $document)->with('success', 'Worker resume generated successfully.');
    }

    public function generateRegistrationForm(GenerateWorkerRegistrationFormRequest $request, Worker $worker, WorkerDocumentGenerationService $service, ActivityLogger $logger): RedirectResponse
    {
        $document = $service->generateRegistrationForm($worker, $request->user()->id);
        $logger->log('generated', 'worker-registration-documents', $document, 'Worker registration form generated.', ['version' => $document->version_number]);
        return redirect()->route('admin.worker-generated-documents.show', $document)->with('success', 'Worker registration form generated successfully.');
    }

    public function show(WorkerGeneratedDocument $document): View
    {
        $document->load(['worker', 'generator']);
        return view('admin.worker-generated-documents.show', compact('document'));
    }

    public function preview(WorkerGeneratedDocument $document, WorkerDocumentGenerationService $service): View
    {
        $document->load('worker');
        return view('admin.worker-generated-documents.preview', $service->snapshotData($document) + ['document' => $document, 'previewDocument' => true]);
    }

    public function print(WorkerGeneratedDocument $document, WorkerDocumentGenerationService $service): View
    {
        $this->authorizeType($document, 'print');
        $document->load('worker');
        return view('admin.worker-generated-documents.print', $service->snapshotData($document) + ['document' => $document, 'printMode' => true]);
    }

    public function download(WorkerGeneratedDocument $document, ActivityLogger $logger): mixed
    {
        $this->authorizeType($document, 'download');
        abort_unless($document->pdf_path && Storage::disk('local')->exists($document->pdf_path), 404);
        $logger->log('downloaded', $this->module($document), $document, 'Worker generated document downloaded.');
        return Storage::disk('local')->download($document->pdf_path, ($document->document_number ?: 'worker-document').'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function whatsapp(ShareWorkerGeneratedDocumentRequest $request, WorkerGeneratedDocument $document, ActivityLogger $logger): RedirectResponse
    {
        $this->authorizeType($document, 'share');
        abort_unless($document->pdf_path && Storage::disk('local')->exists($document->pdf_path), 422, 'Generate the document PDF first.');
        $link = DocumentShareLink::create([
            'token' => Str::random(64), 'document_type' => 'worker_generated_document', 'document_id' => $document->id,
            'expires_at' => now()->addDays(7), 'is_active' => true, 'created_by' => $request->user()->id,
        ]);
        $document->update(['status' => WorkerGeneratedDocumentStatus::Shared, 'shared_at' => now()]);
        $url = route('shared.worker-generated-documents.download', $link->token);
        $snapshot = $document->snapshot_json;
        $worker = $snapshot['worker'] ?? [];
        $message = $document->document_type === WorkerGeneratedDocumentType::Resume
            ? "Hello,\n\nPlease find the Helper Home worker profile below.\n\nWorker: {$worker['name']}\nService: ".(($snapshot['services'][0]['name'] ?? 'Worker profile'))."\nExperience: ".($worker['years_of_experience'] ?? '—')." years\n\nResume: {$url}\n\nThank you,\nHelper Home"
            : "Hello,\n\nWorker Registration Form for:\n{$worker['name']}\nWorker Code: {$worker['worker_code']}\n\nDocument: {$url}\n\nThank you,\nHelper Home";
        $phone = preg_replace('/\D+/', '', (string) ($worker['mobile_number'] ?? ''));
        $logger->log('shared', $this->module($document), $document, 'Worker generated document shared through an expiring link.');
        return redirect()->away('https://wa.me/'.$phone.'?text='.rawurlencode($message));
    }

    public function archive(ArchiveWorkerGeneratedDocumentRequest $request, WorkerGeneratedDocument $document, ActivityLogger $logger): RedirectResponse
    {
        $document->update(['status' => WorkerGeneratedDocumentStatus::Archived]);
        $logger->log('archived', $this->module($document), $document, 'Worker generated document archived.');
        return back()->with('success', 'Generated document archived.');
    }

    private function previewData(Worker $worker, WorkerGeneratedDocumentType $type, WorkerDocumentGenerationService $service): array
    {
        $snapshot = $type === WorkerGeneratedDocumentType::Resume ? $service->buildResumeSnapshot($worker) : $service->buildRegistrationSnapshot($worker);
        $document = new WorkerGeneratedDocument(['worker_id' => $worker->id, 'document_type' => $type, 'version_number' => 0]);
        $document->setRelation('worker', $worker);
        return $service->templateData($document, $snapshot, $worker) + ['document' => $document, 'snapshot' => $snapshot, 'previewDocument' => false];
    }

    private function authorizeType(WorkerGeneratedDocument $document, string $action): void
    {
        $permission = $document->document_type === WorkerGeneratedDocumentType::Resume ? 'worker-resumes.'.$action : 'worker-registration-documents.'.$action;
        abort_unless(request()->user()?->hasPermission($permission), 403);
    }

    private function module(WorkerGeneratedDocument $document): string
    {
        return $document->document_type === WorkerGeneratedDocumentType::Resume ? 'worker-resumes' : 'worker-registration-documents';
    }
}
