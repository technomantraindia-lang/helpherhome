<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateWorkerRegistrationFormRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-registration-documents.generate') ?? false; }
    public function rules(): array { return []; }
}
