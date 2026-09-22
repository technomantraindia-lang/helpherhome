<?php

namespace App\Http\Controllers;

use App\Models\DocumentShareLink;
use App\Models\WorkerGeneratedDocument;
use Illuminate\Support\Facades\Storage;

class SharedWorkerGeneratedDocumentController extends Controller
{
    public function __invoke(string $token): mixed
    {
        $link = DocumentShareLink::where('token', $token)->where('document_type', 'worker_generated_document')->where('is_active', true)->firstOrFail();
        abort_if($link->expires_at && $link->expires_at->isPast(), 410);
        $document = WorkerGeneratedDocument::findOrFail($link->document_id);
        abort_unless($document->pdf_path && Storage::disk('local')->exists($document->pdf_path), 404);
        return Storage::disk('local')->download($document->pdf_path, ($document->document_number ?: 'worker-document').'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
