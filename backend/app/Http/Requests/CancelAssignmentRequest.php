<?php
namespace App\Http\Requests;use Illuminate\Foundation\Http\FormRequest;
class CancelAssignmentRequest extends FormRequest { public function authorize():bool{return $this->user()?->hasPermission('assignments.status')??false;}protected function prepareForValidation():void{$this->merge(['still_required'=>$this->boolean('still_required')]);}public function rules():array{return ['reason'=>['required','string','max:1000'],'still_required'=>['required','boolean']];} }
