<?php

namespace App\Http\Requests;

use App\Enums\InterviewResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerInterviewRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-interviews.create') ?? false; }
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'exists:workers,id'], 'interview_date' => ['nullable', 'date'],
            'interviewer_user_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'service_id' => ['nullable', Rule::exists('services', 'id')->where('is_active', true)],
            'experience_assessment' => ['nullable', 'string', 'max:5000'], 'skills_assessment' => ['nullable', 'string', 'max:5000'],
            'communication_assessment' => ['nullable', 'string', 'max:5000'], 'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'preferred_duty_type_id' => ['nullable', Rule::exists('duty_types', 'id')->where('is_active', true)],
            'preferred_location' => ['nullable', 'string', 'max:255'], 'interview_notes' => ['nullable', 'string', 'max:5000'],
            'result' => ['required', Rule::enum(InterviewResult::class)],
        ];
    }
}
