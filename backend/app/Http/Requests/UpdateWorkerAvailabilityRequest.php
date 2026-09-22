<?php

namespace App\Http\Requests;

use App\Enums\AvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerAvailabilityRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-availability.edit') ?? false; }
    public function rules(): array { return ['availability_status' => ['required', Rule::enum(AvailabilityStatus::class)]]; }
}
