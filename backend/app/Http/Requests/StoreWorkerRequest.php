<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhoneNumbers;
use App\Http\Requests\Concerns\WorkerValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkerRequest extends FormRequest
{
    use NormalizesPhoneNumbers, WorkerValidationRules;
    public function authorize(): bool { return $this->user()?->hasPermission('workers.create') ?? false; }
    protected function prepareForValidation(): void { $this->normalizeWorkerInput(); }
    public function rules(): array { return $this->workerRules(true); }
}
