<?php

namespace App\Http\Requests;

use App\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerDocumentVerificationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-documents.verify') ?? false; }
    public function rules(): array { return ['verification_status' => ['required', Rule::in([VerificationStatus::Verified->value, VerificationStatus::Rejected->value])], 'remarks' => ['nullable', 'string', 'max:1000']]; }
}
