<?php

namespace App\Services;

use App\Enums\OverallVerificationStatus;
use App\Enums\VerificationStatus;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkerManager
{
    public function create(array $data, int $actorId): Worker
    {
        return DB::transaction(function () use ($data, $actorId) {
            $services = Arr::pull($data, 'services', []);
            $references = Arr::pull($data, 'references', []);
            $documents = Arr::pull($data, 'documents', []);
            $photo = Arr::pull($data, 'photo');
            $data['worker_code'] = 'PENDING-'.Str::uuid();
            $data['created_by'] = $actorId;
            $data['updated_by'] = $actorId;
            $data['worker_status'] = $data['worker_status'] ?? \App\Enums\WorkerStatus::Active;
            $data['availability_status'] = $data['availability_status'] ?? \App\Enums\AvailabilityStatus::Available;
            $data['age'] = $this->age($data['date_of_birth'] ?? null, $data['age'] ?? null);

            if (empty($data['registration_number']) || $data['registration_number'] === 'Generated after save') {
                unset($data['registration_number']);
            }
            $worker = Worker::create($data);
            $regNumber = !empty($data['registration_number']) && $data['registration_number'] !== 'Generated after save'
                ? $data['registration_number']
                : sprintf('HH-WRK-REG-%06d', $worker->id);
            $worker->forceFill([
                'worker_code' => sprintf('HH-WRK-%06d', $worker->id),
                'registration_number' => $regNumber,
            ])->saveQuietly();

            if ($photo instanceof UploadedFile) {
                $worker->updateQuietly(['photo_path' => $photo->store("workers/{$worker->id}/photo", 'public')]);
            }

            $worker->services()->sync($services);
            $worker->references()->createMany($references);
            $worker->verification()->create([
                'overall_verification_status' => OverallVerificationStatus::Pending,
            ]);
            $worker->statusHistory()->create([
                'old_status' => null, 'new_status' => $worker->worker_status->value,
                'changed_by' => $actorId, 'reason' => 'Initial registration',
            ]);
            $this->storeDocuments($worker, $documents);

            return $worker->fresh();
        });
    }

    public function update(Worker $worker, array $data, int $actorId): Worker
    {
        return DB::transaction(function () use ($worker, $data, $actorId) {
            $services = Arr::pull($data, 'services', []);
            $references = Arr::pull($data, 'references', []);
            $documents = Arr::pull($data, 'documents', []);
            $photo = Arr::pull($data, 'photo');
            unset($data['worker_code']);
            if (isset($data['registration_number']) && ($data['registration_number'] === 'Generated after save' || trim((string)$data['registration_number']) === '')) {
                if (!$worker->registration_number || $worker->registration_number === 'Generated after save') {
                    $data['registration_number'] = sprintf('HH-WRK-REG-%06d', $worker->id);
                } else {
                    unset($data['registration_number']);
                }
            } elseif (!$worker->registration_number || $worker->registration_number === 'Generated after save') {
                $data['registration_number'] = sprintf('HH-WRK-REG-%06d', $worker->id);
            }
            $oldStatus = $worker->worker_status->value;
            $data['updated_by'] = $actorId;
            $data['age'] = $this->age($data['date_of_birth'] ?? null, $data['age'] ?? null);

            if ($photo instanceof UploadedFile) {
                $oldPhoto = $worker->photo_path;
                $data['photo_path'] = $photo->store("workers/{$worker->id}/photo", 'public');
                if ($oldPhoto) Storage::disk('public')->delete($oldPhoto);
            }

            $worker->update($data);
            $worker->services()->sync($services);
            $worker->references()->delete();
            $worker->references()->createMany($references);
            $this->storeDocuments($worker, $documents);

            if ($oldStatus !== $worker->worker_status->value) {
                $worker->statusHistory()->create([
                    'old_status' => $oldStatus, 'new_status' => $worker->worker_status->value,
                    'changed_by' => $actorId, 'reason' => 'Updated from worker profile',
                ]);
            }

            return $worker->fresh();
        });
    }

    private function storeDocuments(Worker $worker, array $documents): void
    {
        foreach ($documents as $document) {
            $file = Arr::pull($document, 'file');
            if (! $file instanceof UploadedFile) continue;
            $document['file_path'] = $file->store("workers/{$worker->id}/documents", 'local');
            $document['verification_status'] = VerificationStatus::Pending;
            $worker->documents()->create($document);
        }
    }

    private function age(?string $dateOfBirth, mixed $fallback): ?int
    {
        return $dateOfBirth ? Carbon::parse($dateOfBirth)->age : ($fallback === null ? null : (int) $fallback);
    }
}
