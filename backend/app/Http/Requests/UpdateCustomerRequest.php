<?php
namespace App\Http\Requests;
use App\Enums\CustomerStatus;
use App\Http\Requests\Concerns\NormalizesPhoneNumbers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateCustomerRequest extends FormRequest
{
    use NormalizesPhoneNumbers;
    public function authorize(): bool { return $this->user()?->hasPermission('customers.edit') ?? false; }
    protected function prepareForValidation(): void { $this->merge(['mobile_number'=>$this->digits($this->input('mobile_number')),'alternate_mobile_number'=>$this->digits($this->input('alternate_mobile_number'))]); }
    public function rules(): array { return ['registration_date'=>['required','date'],'registration_number'=>['nullable','string','max:255',Rule::unique('customers')->ignore($this->route('customer'))],'name'=>['required','string','max:255'],'mobile_number'=>['required','digits_between:10,15'],'alternate_mobile_number'=>['nullable','digits_between:10,15'],'email'=>['nullable','email','max:255'],'address_line_1'=>['nullable','string','max:255'],'address_line_2'=>['nullable','string','max:255'],'location'=>['nullable','string','max:255'],'city'=>['nullable','string','max:255'],'state'=>['nullable','string','max:255'],'pincode'=>['nullable','string','max:12'],'country'=>['required','string','max:255'],'status'=>['required',Rule::enum(CustomerStatus::class)],'notes'=>['nullable','string','max:5000']]; }
}
