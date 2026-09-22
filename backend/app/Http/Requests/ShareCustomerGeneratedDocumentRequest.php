<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareCustomerGeneratedDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('customer-registration-documents.share') ?? false; }
    public function rules(): array { return []; }
}
