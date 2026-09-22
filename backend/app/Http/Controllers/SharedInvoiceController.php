<?php
namespace App\Http\Controllers;
use App\Enums\InvoiceStatus;
use App\Models\DocumentShareLink;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
class SharedInvoiceController extends Controller
{
    public function __invoke(string $token): mixed
    {
        $link = DocumentShareLink::where('token', $token)->where('document_type', 'invoice')->where('is_active', true)->firstOrFail();
        abort_if($link->expires_at && $link->expires_at->isPast(), 410);
        $invoice = Invoice::findOrFail($link->document_id);
        abort_if($invoice->status === InvoiceStatus::Cancelled, 410);
        abort_unless($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404);
        return Storage::disk('local')->download($invoice->pdf_path, $invoice->invoice_number.'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
