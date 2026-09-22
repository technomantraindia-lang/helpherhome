<?php

namespace App\Http\Requests;

class UpdateWorkerInterviewRequest extends StoreWorkerInterviewRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('worker-interviews.edit') ?? false; }
}
