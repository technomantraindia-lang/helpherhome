<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InterviewResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerInterviewRequest;
use App\Http\Requests\UpdateWorkerInterviewRequest;
use App\Models\DutyType;
use App\Models\Service;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerInterview;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerInterviewController extends Controller
{
    public function index(Request $request): View
    {
        $interviews = WorkerInterview::with(['worker:id,worker_code,name', 'interviewer:id,name', 'service:id,name'])
            ->when($request->filled('result'), fn ($q) => $q->where('result', $request->input('result')))
            ->when($request->filled('service'), fn ($q) => $q->where('service_id', $request->integer('service')))
            ->when($request->filled('interviewer'), fn ($q) => $q->where('interviewer_user_id', $request->integer('interviewer')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('interview_date', $request->input('date')))
            ->latest('interview_date')->paginate(20)->withQueryString();
        return view('admin.worker-interviews.index', ['interviews' => $interviews, 'services' => Service::where('is_active', true)->get(), 'interviewers' => User::where('is_active', true)->orderBy('name')->get(), 'results' => InterviewResult::cases()]);
    }

    public function create(Request $request): View
    {
        $interview = new WorkerInterview(['worker_id' => $request->integer('worker') ?: null, 'result' => InterviewResult::Pending]);
        return view('admin.worker-interviews.form', $this->formData($interview));
    }

    public function store(StoreWorkerInterviewRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $interview = WorkerInterview::create($request->validated());
        $logger->log('created', 'worker-interviews', $interview, 'Worker interview created.');
        return redirect()->route('admin.worker-interviews.index')->with('success', 'Interview saved successfully.');
    }

    public function edit(WorkerInterview $interview): View
    {
        return view('admin.worker-interviews.form', $this->formData($interview));
    }

    public function update(UpdateWorkerInterviewRequest $request, WorkerInterview $interview, ActivityLogger $logger): RedirectResponse
    {
        $interview->update($request->validated());
        $logger->log('updated', 'worker-interviews', $interview, 'Worker interview updated.');
        return redirect()->route('admin.worker-interviews.index')->with('success', 'Interview updated successfully.');
    }

    private function formData(WorkerInterview $interview): array
    {
        return ['interview' => $interview, 'workers' => Worker::orderBy('name')->get(['id', 'worker_code', 'name']), 'services' => Service::where('is_active', true)->orderBy('name')->get(), 'dutyTypes' => DutyType::where('is_active', true)->orderBy('sort_order')->get(), 'interviewers' => User::where('is_active', true)->orderBy('name')->get(), 'results' => InterviewResult::cases()];
    }
}
