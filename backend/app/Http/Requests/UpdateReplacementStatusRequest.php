<?php
namespace App\Http\Requests;use App\Enums\ReplacementStatus;use Illuminate\Foundation\Http\FormRequest;use Illuminate\Validation\Rule;
class UpdateReplacementStatusRequest extends FormRequest { public function authorize():bool{return $this->user()?->hasPermission('replacements.manage')??false;}public function rules():array{return ['status'=>['required',Rule::enum(ReplacementStatus::class)],'reason'=>['nullable','string','max:1000']];} }
