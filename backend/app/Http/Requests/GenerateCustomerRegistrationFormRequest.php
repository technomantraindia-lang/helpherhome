<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCustomerRegistrationFormRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('customer-registration-documents.generate') ?? false; }
    public function rules(): array { return []; }
}
