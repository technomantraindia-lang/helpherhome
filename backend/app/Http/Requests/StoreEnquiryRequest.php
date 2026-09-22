<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreEnquiryRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('enquiries.create') ?? false; } public function rules(): array { return ['name'=>'required|string|max:120','mobile_number'=>'required|string|min:10|max:30','email'=>'nullable|email|max:190','city'=>'nullable|string|max:100','area'=>'nullable|string|max:120','service_id'=>'nullable|exists:services,id','duty_type_id'=>'nullable|exists:duty_types,id','preferred_start_date'=>'nullable|date','required_gender'=>'nullable|string|max:30','number_of_persons'=>'nullable|integer|min:1|max:50','preferred_contact_method'=>'nullable|string|max:30','message'=>'nullable|string|max:5000','source'=>'nullable|in:website,manual,phone,whatsapp,social,other','notes'=>'nullable|string|max:5000']; } }
