<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareWorkerGeneratedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');
        $permission = $document?->document_type?->value === 'worker_resume' ? 'worker-resumes.share' : 'worker-registration-documents.share';
        return $this->user()?->hasPermission($permission) ?? false;
    }
    public function rules(): array { return []; }
}
