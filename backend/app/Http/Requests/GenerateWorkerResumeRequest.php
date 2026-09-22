<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateWorkerResumeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-resumes.generate') ?? false; }
    public function rules(): array { return []; }
}
