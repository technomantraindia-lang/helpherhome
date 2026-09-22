<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreEnquiryFollowUpRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('enquiries.follow-up') ?? false; } public function rules(): array { return ['follow_up_date'=>'required|date','follow_up_time'=>'nullable|date_format:H:i','notes'=>'nullable|string|max:2000','assigned_to'=>'nullable|exists:users,id']; } }
