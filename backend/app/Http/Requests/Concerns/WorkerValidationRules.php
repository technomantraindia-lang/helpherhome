<?php

namespace App\Http\Requests\Concerns;

use App\Enums\AvailabilityStatus;
use App\Enums\WorkerStatus;
use Illuminate\Validation\Rule;

trait WorkerValidationRules
{
    protected function workerRules(bool $creating): array
    {
        return [
            'worker_code' => ['prohibited'],
            'registration_number' => ['nullable', 'string', 'max:100', Rule::unique('workers')->ignore($this->route('worker'))],
            'registration_date' => ['required', 'date'], 'date_of_joining' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'mobile_number' => ['required', 'string', 'min:10', 'max:15'],
            'alternate_mobile_number' => ['nullable', 'string', 'min:10', 'max:15'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'], 'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'], 'state' => ['required', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:12'], 'country' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'], 'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'widowed', 'divorced', 'other'])],
            'years_of_experience' => ['nullable', 'numeric', 'min:0', 'max:80'], 'experience_notes' => ['nullable', 'string', 'max:5000'],
            'salary_expectation' => ['nullable', 'numeric', 'min:0'], 'preferred_work_location' => ['nullable', 'string', 'max:255'],
            'preferred_duty_type_id' => ['nullable', Rule::exists('duty_types', 'id')->where('is_active', true)],
            'availability_status' => ['required', Rule::enum(AvailabilityStatus::class)],
            'worker_status' => ['required', Rule::enum(WorkerStatus::class)],
            'police_station_name' => ['nullable', 'string', 'max:255'],
            'supervisor_user_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'executive_user_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'registration_source' => ['nullable', Rule::in(['admin', 'staff', 'walk_in', 'website', 'other'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'references' => ['required', 'array', 'min:5'],
            'references.*.name' => ['required', 'string', 'max:255'],
            'references.*.mobile_number' => ['required', 'string', 'min:10', 'max:15'],
            'references.*.relation' => ['required', 'string', 'max:100'],
            'references.*.occupation' => ['nullable', 'string', 'max:255'],
            'references.*.address' => ['nullable', 'string', 'max:1000'],
            'references.*.notes' => ['nullable', 'string', 'max:1000'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required_with:documents.*.file', 'nullable', 'string', 'max:100'],
            'documents.*.document_name' => ['nullable', 'string', 'max:255'],
            'documents.*.document_number' => ['nullable', 'string', 'max:255'],
            'documents.*.file' => [$creating ? 'required_with:documents.*.document_type' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'documents.*.issue_date' => ['nullable', 'date'], 'documents.*.expiry_date' => ['nullable', 'date', 'after_or_equal:documents.*.issue_date'],
            'documents.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function normalizeWorkerInput(): void
    {
        $references = collect($this->input('references', []))->map(function ($reference) {
            $reference['mobile_number'] = $this->digits($reference['mobile_number'] ?? null);
            return $reference;
        })->all();

        $this->merge([
            'mobile_number' => $this->digits($this->input('mobile_number')),
            'alternate_mobile_number' => $this->digits($this->input('alternate_mobile_number')),
            'references' => $references,
        ]);
    }
}
