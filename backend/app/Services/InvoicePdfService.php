<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoicePdfService
{
    public function generate(Invoice $invoice, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice, $actorId) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status === InvoiceStatus::Cancelled || $invoice->payment_status === PaymentStatus::Paid) {
                throw ValidationException::withMessages(['pdf' => 'Cancelled or paid invoices cannot be regenerated.']);
            }
            $invoice->load(['items', 'assignment.service']);
            $version = $invoice->pdf_version + 1;
            $html = view('admin.invoices.document', $this->documentData($invoice))->render();
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);
            $pdf = new Dompdf($options);
            $pdf->loadHtml($html, 'UTF-8');
            $pdf->setPaper('A4');
            $pdf->render();
            $path = "invoices/{$invoice->invoice_number}/invoice-v{$version}.pdf";
            Storage::disk('local')->put($path, $pdf->output());
            $old = $invoice->status->value;
            $invoice->update(['pdf_path' => $path, 'pdf_generated_at' => now(), 'pdf_version' => $version,
                'status' => $old === 'draft' ? InvoiceStatus::Generated : $invoice->status, 'updated_by' => $actorId]);
            $invoice->versions()->create(['version_number' => $version, 'snapshot_json' => [
                'invoice' => $invoice->fresh()->attributesToArray(), 'items' => $invoice->items->toArray(),
            ], 'pdf_path' => $path, 'created_by' => $actorId]);
            if ($old === 'draft') $invoice->statusHistory()->create(['old_status' => 'draft', 'new_status' => 'generated', 'changed_by' => $actorId, 'reason' => 'PDF generated']);
            return $invoice->fresh();
        });
    }

    public function documentData(Invoice $invoice): array
    {
        return ['invoice' => $invoice, 'logoData' => $this->dataUri($invoice->agency_logo_snapshot),
            'qrData' => $this->dataUri($invoice->upi_qr_snapshot), 'signatureData' => $this->dataUri($invoice->signature_path_snapshot),
            'stampData' => $this->dataUri($invoice->stamp_path_snapshot)];
    }

    private function dataUri(?string $path): ?string
    {
        if (!$path || !Storage::disk('local')->exists($path)) return null;
        $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';
        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
