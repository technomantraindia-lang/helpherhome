<?php

namespace App\Http\Requests;

use App\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerVerificationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-verification.edit') ?? false; }
    public function rules(): array
    {
        $status = ['required', Rule::enum(VerificationStatus::class)];
        return [
            'document_verification_status' => $status, 'document_verification_date' => ['nullable', 'date'],
            'police_verification_status' => $status, 'police_verification_date' => ['nullable', 'date'],
            'background_verification_status' => $status, 'background_verification_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
