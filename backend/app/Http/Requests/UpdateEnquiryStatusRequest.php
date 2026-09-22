<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateEnquiryStatusRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('enquiries.status') ?? false; } public function rules(): array { return ['status'=>'required|in:new,contacted,follow_up,qualified,converted,not_interested,invalid,closed','notes'=>'nullable|string|max:2000']; } }
