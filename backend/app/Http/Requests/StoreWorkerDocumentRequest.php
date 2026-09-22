<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkerDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-documents.create') ?? false; }
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:100'], 'document_name' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'], 'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'issue_date' => ['nullable', 'date'], 'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
