<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveWorkerGeneratedDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-generated-documents.view') ?? false; }
    public function rules(): array { return []; }
}
