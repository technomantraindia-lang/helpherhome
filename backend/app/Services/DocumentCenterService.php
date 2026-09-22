<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\CustomerGeneratedDocument;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\WorkerGeneratedDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class DocumentCenterService
{
    public function search(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $documents = $this->aggregate()->filter(function (array $document) use ($filters) {
            if ($document['document_type'] === 'worker_registration_form' && !request()->user()?->hasPermission('worker-registration-documents.view')) return false;
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '' && !str_contains(mb_strtolower(implode(' ', array_filter([$document['document_number'], $document['related_name'], $document['related_code'], $document['customer_mobile'], $document['requirement_code'], $document['assignment_code']])), 'UTF-8'), mb_strtolower($search, 'UTF-8'))) return false;
            if (!empty($filters['document_type']) && $document['document_type'] !== $filters['document_type']) return false;
            if (!empty($filters['status']) && $document['status'] !== $filters['status']) return false;
            if (!empty($filters['customer_id']) && (int) $document['customer_id'] !== (int) $filters['customer_id']) return false;
            if (!empty($filters['worker_id']) && (int) $document['worker_id'] !== (int) $filters['worker_id']) return false;
            $date = $document['generated_at']?->toDateString();
            if (!empty($filters['quick'])) {
                $quickFrom = match ($filters['quick']) {
                    'today' => today()->toDateString(),
                    'week' => now()->startOfWeek()->toDateString(),
                    'month' => now()->startOfMonth()->toDateString(),
                    default => null,
                };
                if ($quickFrom && (!$date || $date < $quickFrom)) return false;
            }
            if (!empty($filters['from']) && (!$date || $date < $filters['from'])) return false;
            if (!empty($filters['to']) && (!$date || $date > $filters['to'])) return false;
            return true;
        })->sortByDesc(fn (array $document) => $document['generated_at']?->timestamp ?? 0)->values();

        $page = max(1, (int) ($filters['page'] ?? request()->integer('page', 1)));
        return new \Illuminate\Pagination\LengthAwarePaginator($documents->forPage($page, $perPage)->values(), $documents->count(), $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
    }

    public function aggregate(): Collection
    {
        $documents = collect();
        WorkerGeneratedDocument::with(['worker:id,worker_code,name,mobile_number'])->get()->each(function ($document) use ($documents) {
            $documents->push($this->row($document->document_type->value, $document->document_number, $document->worker?->name, $document->worker?->worker_code, $document->status->value, $document->generated_at, $document->worker?->mobile_number, $document->worker_id, null, 'worker', $document->id));
        });
        CustomerGeneratedDocument::with(['customer:id,customer_code,name,mobile_number', 'requirement:id,requirement_code'])->get()->each(function ($document) use ($documents) {
            $documents->push($this->row($document->document_type->value, $document->document_number, $document->customer?->name, $document->customer?->customer_code, $document->status->value, $document->generated_at, $document->customer?->mobile_number, null, $document->customer_id, 'customer', $document->id, $document->requirement?->requirement_code));
        });
        Agreement::with(['customer:id,customer_code,name,mobile_number', 'worker:id,worker_code,name,mobile_number'])->get()->each(function ($document) use ($documents) {
            $documents->push($this->row('agreement', $document->agreement_code, $document->customer?->name ?: $document->worker?->name, $document->agreement_code, $document->status->value, $document->pdf_generated_at ?: $document->created_at, $document->customer?->mobile_number, $document->worker_id, $document->customer_id, 'agreement', $document->id));
        });
        Invoice::with(['customer:id,customer_code,name,mobile_number'])->get()->each(function ($document) use ($documents) {
            $documents->push($this->row('invoice', $document->invoice_number, $document->customer?->name, $document->invoice_number, $document->status->value, $document->pdf_generated_at ?: $document->created_at, $document->customer?->mobile_number, null, $document->customer_id, 'invoice', $document->id));
        });
        PaymentReceipt::with(['customer:id,customer_code,name,mobile_number', 'invoice:id,invoice_number', 'payment:id,status'])->get()->each(function ($document) use ($documents) {
            $documents->push($this->row('payment_receipt', $document->receipt_number, $document->customer_name_snapshot ?: $document->customer?->name, $document->receipt_number, $document->payment?->status?->value ?: 'generated', $document->pdf_generated_at ?: $document->created_at, $document->customer_mobile_snapshot ?: $document->customer?->mobile_number, null, $document->customer_id, 'payment_receipt', $document->id, null, $document->invoice?->invoice_number));
        });
        return $documents;
    }

    private function row(string $type, ?string $number, ?string $name, ?string $code, ?string $status, $generatedAt, ?string $mobile, ?int $workerId, ?int $customerId, string $sourceType, int $sourceId, ?string $requirementCode = null, ?string $assignmentCode = null): array
    {
        return ['document_type' => $type, 'document_number' => $number, 'related_name' => $name, 'related_code' => $code, 'status' => $status ?: 'generated', 'generated_at' => $generatedAt, 'customer_mobile' => $mobile, 'worker_id' => $workerId, 'customer_id' => $customerId, 'source_type' => $sourceType, 'source_id' => $sourceId, 'requirement_code' => $requirementCode, 'assignment_code' => $assignmentCode];
    }
}
