<?php

namespace App\Http\Controllers;

use App\Models\CustomerGeneratedDocument;
use App\Models\DocumentShareLink;
use Illuminate\Support\Facades\Storage;

class SharedCustomerGeneratedDocumentController extends Controller
{
    public function __invoke(string $token): mixed
    {
        $link = DocumentShareLink::where('token', $token)->where('document_type', 'customer_generated_document')->where('is_active', true)->firstOrFail();
        abort_if($link->expires_at && $link->expires_at->isPast(), 410);
        $document = CustomerGeneratedDocument::findOrFail($link->document_id);
        abort_unless($document->pdf_path && Storage::disk('local')->exists($document->pdf_path), 404);
        return Storage::disk('local')->download($document->pdf_path, ($document->document_number ?: 'customer-registration').'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
