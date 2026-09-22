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
            $data['age'] = $this->age($data['date_of_birth'] ?? null, $data['age'] ?? null);

            $worker = Worker::create($data);
            $worker->forceFill(['worker_code' => sprintf('HH-WRK-%06d', $worker->id)])->saveQuietly();

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
