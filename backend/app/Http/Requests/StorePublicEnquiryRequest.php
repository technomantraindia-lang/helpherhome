<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePublicEnquiryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void
    {
        $this->merge(['mobile_number' => preg_replace('/\D+/', '', (string) $this->input('mobile_number')), 'source' => 'website']);
    }
    public function rules(): array
    {
        return ['name'=>'required|string|min:2|max:120','mobile_number'=>'required|string|min:10|max:15','email'=>'nullable|email|max:190','city'=>'nullable|string|max:100','area'=>'nullable|string|max:120','service_id'=>'nullable|integer|exists:services,id','service_slug'=>'nullable|string|max:100','duty_type_id'=>'nullable|integer|exists:duty_types,id','duty_type_slug'=>'nullable|string|max:100','preferred_start_date'=>'nullable|date','required_gender'=>'nullable|string|max:30','number_of_persons'=>'nullable|integer|min:1|max:50','preferred_contact_method'=>'nullable|string|max:30','message'=>'nullable|string|max:5000','source_page'=>'nullable|string|max:2000','referrer'=>'nullable|string|max:2000','utm_source'=>'nullable|string|max:190','utm_medium'=>'nullable|string|max:190','utm_campaign'=>'nullable|string|max:190','utm_term'=>'nullable|string|max:190','utm_content'=>'nullable|string|max:190','website'=>'nullable|max:0'];
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['success'=>false,'message'=>'Please check the submitted information.','errors'=>$validator->errors()], 422));
    }
}
