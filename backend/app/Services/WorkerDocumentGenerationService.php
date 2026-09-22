<?php

namespace App\Services;

use App\Enums\WorkerGeneratedDocumentStatus;
use App\Enums\WorkerGeneratedDocumentType;
use App\Models\AgencySetting;
use App\Models\Worker;
use App\Models\WorkerGeneratedDocument;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WorkerDocumentGenerationService
{
    public function buildResumeSnapshot(Worker $worker): array
    {
        $worker->loadMissing(['services', 'preferredDutyType', 'verification', 'interviews.service', 'interviews.preferredDutyType', 'references']);
        $agency = AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home']);
        $interview = $worker->interviews->sortByDesc('interview_date')->first();

        return [
            'worker' => $this->workerProfile($worker, false),
            'services' => $worker->services->map(fn ($service) => [
                'name' => $service->name,
                'skill_level' => $service->pivot?->skill_level,
                'experience_years' => $service->pivot?->experience_years,
                'notes' => null,
            ])->values()->all(),
            'verification' => $this->verification($worker),
            'interview' => $interview ? [
                'result' => $this->value($interview->result),
                'experience_assessment' => $interview->experience_assessment,
                'skills_assessment' => $interview->skills_assessment,
                'communication_assessment' => $interview->communication_assessment,
            ] : null,
            'references' => $worker->references->map(fn ($reference) => [
                'name' => $reference->name,
                'relation' => $reference->relation,
                'mobile_number' => $this->maskedPhone($reference->mobile_number),
            ])->values()->all(),
            'agency' => $this->agency($agency),
        ];
    }

    public function buildRegistrationSnapshot(Worker $worker): array
    {
        $worker->loadMissing(['services', 'preferredDutyType', 'verification', 'references', 'documents', 'supervisor', 'executive']);
        $agency = AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home']);

        return [
            'worker' => $this->workerProfile($worker, true),
            'services' => $worker->services->map(fn ($service) => [
                'name' => $service->name,
                'skill_level' => $service->pivot?->skill_level,
                'experience_years' => $service->pivot?->experience_years,
                'notes' => $service->pivot?->notes,
            ])->values()->all(),
            'identity_documents' => $worker->documents->map(fn ($document) => [
                'document_type' => $document->document_type,
                'document_name' => $document->document_name,
                'document_number' => $document->document_number,
                'verification_status' => $this->value($document->verification_status),
            ])->values()->all(),
            'verification' => $this->verification($worker),
            'internal' => [
                'supervisor_name' => $worker->supervisor?->name,
                'executive_name' => $worker->executive?->name,
                'police_station_name' => $worker->police_station_name,
            ],
            'references' => $worker->references->map(fn ($reference) => [
                'name' => $reference->name,
                'mobile_number' => $reference->mobile_number,
                'relation' => $reference->relation,
                'occupation' => $reference->occupation,
                'address' => $reference->address,
            ])->values()->all(),
            'agency' => $this->agency($agency),
        ];
    }

    public function generateResume(Worker $worker, int $actorId): WorkerGeneratedDocument
    {
        return $this->generate($worker, WorkerGeneratedDocumentType::Resume, $actorId);
    }

    public function generateRegistrationForm(Worker $worker, int $actorId): WorkerGeneratedDocument
    {
        return $this->generate($worker, WorkerGeneratedDocumentType::RegistrationForm, $actorId);
    }

    public function generate(Worker $worker, WorkerGeneratedDocumentType $type, int $actorId): WorkerGeneratedDocument
    {
        return DB::transaction(function () use ($worker, $type, $actorId) {
            $lockedWorker = Worker::whereKey($worker->id)->lockForUpdate()->firstOrFail();
            $version = ((int) WorkerGeneratedDocument::where('worker_id', $lockedWorker->id)->where('document_type', $type->value)->max('version_number')) + 1;
            $snapshot = $type === WorkerGeneratedDocumentType::Resume
                ? $this->buildResumeSnapshot($lockedWorker)
                : $this->buildRegistrationSnapshot($lockedWorker);
            $document = WorkerGeneratedDocument::create([
                'worker_id' => $lockedWorker->id,
                'document_type' => $type,
                'document_number' => sprintf('HH-%s-%s-V%d', $type->shortCode(), $lockedWorker->worker_code, $version),
                'version_number' => $version,
                'status' => WorkerGeneratedDocumentStatus::Draft,
                'snapshot_json' => $snapshot,
                'generated_by' => $actorId,
            ]);
            $path = "workers/{$lockedWorker->id}/generated/".($type === WorkerGeneratedDocumentType::Resume ? 'resume' : 'registration')."-v{$version}.pdf";
            $html = view($type === WorkerGeneratedDocumentType::Resume ? 'admin.workers.documents.resume-pdf' : 'admin.workers.documents.registration-pdf', $this->templateData($document, $snapshot, $lockedWorker))->render();
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);
            $pdf = new Dompdf($options);
            $pdf->loadHtml($html, 'UTF-8');
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();
            Storage::disk('local')->put($path, $pdf->output());
            $document->update(['pdf_path' => $path, 'generated_at' => now(), 'status' => WorkerGeneratedDocumentStatus::Generated]);
            return $document->fresh(['worker', 'generator']);
        }, 5);
    }

    public function templateData(WorkerGeneratedDocument $document, array $snapshot, ?Worker $worker = null): array
    {
        $worker ??= $document->worker;
        return [
            'document' => $document,
            'snapshot' => $snapshot,
            'photoData' => $this->dataUri($snapshot['worker']['photo_path'] ?? $worker?->photo_path, 'public'),
            'logoData' => $this->dataUri($snapshot['agency']['logo_path'] ?? null, 'public'),
            'signatureData' => $this->dataUri($snapshot['agency']['signature_path'] ?? null, 'local'),
            'stampData' => $this->dataUri($snapshot['agency']['stamp_path'] ?? null, 'local'),
        ];
    }

    public function snapshotData(WorkerGeneratedDocument $document): array
    {
        return $this->templateData($document, $document->snapshot_json, $document->worker);
    }

    private function workerProfile(Worker $worker, bool $internal): array
    {
        $age = $worker->date_of_birth ? Carbon::parse($worker->date_of_birth)->age : $worker->age;
        return [
            'worker_code' => $worker->worker_code,
            'registration_number' => $worker->registration_number,
            'registration_date' => $worker->registration_date?->format('Y-m-d'),
            'date_of_joining' => $worker->date_of_joining?->format('Y-m-d'),
            'name' => $worker->name,
            'photo_path' => $worker->photo_path,
            'mobile_number' => $worker->mobile_number,
            'alternate_mobile_number' => $worker->alternate_mobile_number,
            'email' => $worker->email,
            'address_line_1' => $worker->address_line_1,
            'address_line_2' => $worker->address_line_2,
            'city' => $worker->city,
            'state' => $worker->state,
            'pincode' => $worker->pincode,
            'country' => $worker->country,
            'date_of_birth' => $worker->date_of_birth?->format('Y-m-d'),
            'age' => $age,
            'gender' => $worker->gender,
            'marital_status' => $worker->marital_status,
            'years_of_experience' => $worker->years_of_experience,
            'experience_notes' => $worker->experience_notes,
            'salary_expectation' => $worker->salary_expectation,
            'preferred_work_location' => $worker->preferred_work_location,
            'preferred_duty_type' => $worker->preferredDutyType?->name,
            'availability_status' => $this->value($worker->availability_status),
            'worker_status' => $this->value($worker->worker_status),
            'police_station_name' => $internal ? $worker->police_station_name : null,
            'notes' => $internal ? $worker->notes : null,
        ];
    }

    private function verification(Worker $worker): array
    {
        return [
            'document' => $this->value($worker->verification?->document_verification_status) ?: 'pending',
            'police' => $this->value($worker->verification?->police_verification_status) ?: 'pending',
            'background' => $this->value($worker->verification?->background_verification_status) ?: 'pending',
            'overall' => $this->value($worker->verification?->overall_verification_status) ?: 'pending',
        ];
    }

    private function agency(AgencySetting $agency): array
    {
        return [
            'business_name' => $agency->business_name ?: 'Helper Home',
            'tagline' => $agency->tagline,
            'phone' => $agency->phone_primary,
            'email' => $agency->email,
            'address' => collect([$agency->address_line_1, $agency->address_line_2, $agency->city, $agency->state, $agency->pincode])->filter()->implode(', '),
            'logo_path' => $agency->logo_path,
            'signature_path' => $agency->signature_path,
            'stamp_path' => $agency->stamp_path,
            'authorized_person' => $agency->owner_authorized_person,
        ];
    }

    private function value(mixed $value): ?string
    {
        return $value instanceof \BackedEnum ? $value->value : ($value === null ? null : (string) $value);
    }

    private function maskedPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') return null;
        if (strlen($digits) <= 4) return str_repeat('X', strlen($digits));
        return substr($digits, 0, 2).str_repeat('X', max(0, strlen($digits) - 4)).substr($digits, -2);
    }

    private function dataUri(?string $path, string $disk): ?string
    {
        if (!$path) return null;
        if (!Storage::disk($disk)->exists($path)) {
            $disk = $disk === 'local' ? 'public' : 'local';
        }
        if (!Storage::disk($disk)->exists($path)) return null;
        $mime = Storage::disk($disk)->mimeType($path) ?: 'image/png';
        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk($disk)->get($path));
    }
}
