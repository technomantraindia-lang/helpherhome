<?php
namespace App\Http\Requests;
use App\Enums\CustomerStatus;
use App\Http\Requests\Concerns\CustomerRequirementRules;
use App\Http\Requests\Concerns\NormalizesPhoneNumbers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreCustomerRequest extends FormRequest
{
    use CustomerRequirementRules, NormalizesPhoneNumbers;
    public function authorize(): bool { return $this->user()?->hasPermission('customers.create') && $this->user()?->hasPermission('customer-requirements.create'); }
    protected function prepareForValidation(): void { $this->merge(['mobile_number'=>$this->digits($this->input('mobile_number')),'alternate_mobile_number'=>$this->digits($this->input('alternate_mobile_number')),'force_duplicate'=>$this->boolean('force_duplicate')]); $this->normalizeRequirementInput(); }
    public function rules(): array { return array_merge($this->requirementRules(), ['registration_date'=>['required','date'],'registration_number'=>['nullable','string','max:255','unique:customers,registration_number'],'name'=>['required','string','max:255'],'mobile_number'=>['required','digits_between:10,15'],'alternate_mobile_number'=>['nullable','digits_between:10,15'],'email'=>['nullable','email','max:255'],'address_line_1'=>['nullable','string','max:255'],'address_line_2'=>['nullable','string','max:255'],'location'=>['nullable','string','max:255'],'city'=>['nullable','string','max:255'],'state'=>['nullable','string','max:255'],'pincode'=>['nullable','string','max:12'],'country'=>['required','string','max:255'],'status'=>['required',Rule::enum(CustomerStatus::class)],'notes'=>['nullable','string','max:5000'],'force_duplicate'=>['boolean']]); }
}
