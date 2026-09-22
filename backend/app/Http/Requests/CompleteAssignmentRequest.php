<?php
namespace App\Http\Requests;use Illuminate\Foundation\Http\FormRequest;
class CompleteAssignmentRequest extends FormRequest { public function authorize():bool{return $this->user()?->hasPermission('assignments.status')??false;}public function rules():array{return ['completion_date'=>['required','date'],'reason'=>['nullable','string','max:1000']];} }
