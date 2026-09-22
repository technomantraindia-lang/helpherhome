<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerDocumentRequest;
use App\Http\Requests\UpdateWorkerDocumentVerificationRequest;
use App\Models\Worker;
use App\Models\WorkerDocument;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkerDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $documents = WorkerDocument::with('worker:id,worker_code,name')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($x) => $x->where('document_number', 'like', '%'.$request->input('search').'%')->orWhereHas('worker', fn ($w) => $w->where('name', 'like', '%'.$request->input('search').'%')->orWhere('worker_code', 'like', '%'.$request->input('search').'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('verification_status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('document_type', $request->input('type')))
            ->latest()->paginate(25)->withQueryString();
        return view('admin.worker-documents.index', ['documents' => $documents, 'types' => WorkerDocument::distinct()->orderBy('document_type')->pluck('document_type'), 'statuses' => VerificationStatus::cases()]);
    }

    public function store(StoreWorkerDocumentRequest $request, Worker $worker, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->safe()->except('file');
        $data['file_path'] = $request->file('file')->store("workers/{$worker->id}/documents", 'local');
        $data['verification_status'] = VerificationStatus::Pending;
        $document = $worker->documents()->create($data);
        $logger->log('uploaded', 'worker-documents', $document, 'Worker document uploaded.', ['document_type' => $document->document_type]);
        return back()->with('success', 'Document uploaded successfully.');
    }

    public function view(WorkerDocument $document): StreamedResponse
    {
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);
        return Storage::disk('local')->response($document->file_path);
    }

    public function download(WorkerDocument $document): StreamedResponse
    {
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);
        return Storage::disk('local')->download($document->file_path, ($document->document_name ?: $document->document_type).'.'.pathinfo($document->file_path, PATHINFO_EXTENSION));
    }

    public function verify(UpdateWorkerDocumentVerificationRequest $request, WorkerDocument $document, ActivityLogger $logger): RedirectResponse
    {
        $status = $request->validated('verification_status');
        $document->update(['verification_status' => $status, 'remarks' => $request->validated('remarks'), 'verified_by' => $request->user()->id, 'verified_at' => now()]);
        $logger->log($status === 'verified' ? 'verified' : 'rejected', 'worker-documents', $document, 'Worker document verification updated.', ['status' => $status]);
        return back()->with('success', 'Document verification updated.');
    }
}
