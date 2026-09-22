<?php
namespace App\Http\Requests;
use App\Http\Requests\Concerns\CustomerRequirementRules;
use Illuminate\Foundation\Http\FormRequest;
class StoreCustomerRequirementRequest extends FormRequest
{
    use CustomerRequirementRules;
    public function authorize(): bool { return $this->user()?->hasPermission('customer-requirements.create') ?? false; }
    protected function prepareForValidation(): void { $this->normalizeRequirementInput(); }
    public function rules(): array { return $this->requirementRules(); }
}
