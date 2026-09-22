<?php

namespace App\Http\Requests;

use App\Enums\WorkerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('workers.status') ?? false; }
    public function rules(): array { return ['worker_status' => ['required', Rule::enum(WorkerStatus::class)], 'reason' => ['nullable', 'string', 'max:1000']]; }
}
