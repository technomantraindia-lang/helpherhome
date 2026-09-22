<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AvailabilityStatus;
use App\Enums\OverallVerificationStatus;
use App\Enums\WorkerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Http\Requests\UpdateWorkerStatusRequest;
use App\Models\DutyType;
use App\Models\Service;
use App\Models\User;
use App\Models\Worker;
use App\Services\ActivityLogger;
use App\Services\WorkerManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerController extends Controller
{
    public function index(Request $request): View
    {
        $workers = Worker::query()->with(['services:id,name', 'verification', 'supervisor:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q->where('worker_code', 'like', $term)->orWhere('name', 'like', $term)->orWhere('mobile_number', 'like', $term)->orWhere('city', 'like', $term));
            })
            ->when($request->filled('service'), fn ($query) => $query->whereHas('services', fn ($q) => $q->where('services.id', $request->integer('service'))))
            ->when($request->filled('availability'), fn ($query) => $query->where('availability_status', $request->input('availability')))
            ->when($request->filled('status'), fn ($query) => $query->where('worker_status', $request->input('status')))
            ->when($request->filled('verification'), fn ($query) => $query->whereHas('verification', fn ($q) => $q->where('overall_verification_status', $request->input('verification'))))
            ->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->input('gender')))
            ->when($request->filled('city'), fn ($query) => $query->where('city', $request->input('city')))
            ->when($request->filled('state'), fn ($query) => $query->where('state', $request->input('state')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.workers.index', [
            'workers' => $workers, 'services' => Service::where('is_active', true)->orderBy('sort_order')->get(),
            'cities' => Worker::distinct()->orderBy('city')->pluck('city'), 'states' => Worker::distinct()->orderBy('state')->pluck('state'),
            'availabilityStatuses' => AvailabilityStatus::cases(), 'workerStatuses' => WorkerStatus::cases(),
            'verificationStatuses' => OverallVerificationStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.workers.form', $this->formData(new Worker));
    }

    public function store(StoreWorkerRequest $request, WorkerManager $manager, ActivityLogger $logger): RedirectResponse
    {
        $worker = $manager->create($request->validated(), $request->user()->id);
        $logger->log('created', 'workers', $worker, 'Worker registered.', ['worker_code' => $worker->worker_code]);
        return redirect()->route('admin.workers.show', $worker)->with('success', "Worker {$worker->worker_code} registered successfully.");
    }

    public function show(Worker $worker): View
    {
        $worker->load(['services', 'references', 'documents.verifier', 'interviews.interviewer', 'interviews.service', 'verification.documentVerifier', 'verification.policeVerifier', 'verification.backgroundVerifier', 'statusHistory.changedBy', 'preferredDutyType', 'supervisor', 'executive', 'assignments.customer', 'assignments.service', 'replacementsAsOldWorker.customer', 'replacementsAsNewWorker.customer', 'agreements.customer', 'agreements.service', 'generatedDocuments.generator']);
        return view('admin.workers.show', compact('worker'));
    }

    public function edit(Worker $worker): View
    {
        $worker->load(['services', 'references']);
        return view('admin.workers.form', $this->formData($worker));
    }

    public function update(UpdateWorkerRequest $request, Worker $worker, WorkerManager $manager, ActivityLogger $logger): RedirectResponse
    {
        $oldStatus = $worker->worker_status->value;
        $worker = $manager->update($worker, $request->validated(), $request->user()->id);
        $logger->log('updated', 'workers', $worker, 'Worker profile updated.', ['worker_code' => $worker->worker_code]);
        if ($oldStatus !== $worker->worker_status->value) $logger->log('status_changed', 'workers', $worker, 'Worker status changed.', ['from' => $oldStatus, 'to' => $worker->worker_status->value]);
        return redirect()->route('admin.workers.show', $worker)->with('success', 'Worker profile updated successfully.');
    }

    public function updateStatus(UpdateWorkerStatusRequest $request, Worker $worker, ActivityLogger $logger): RedirectResponse
    {
        $oldStatus = $worker->worker_status->value;
        $newStatus = $request->validated('worker_status');
        if ($oldStatus !== $newStatus) {
            $worker->update(['worker_status' => $newStatus, 'updated_by' => $request->user()->id]);
            $worker->statusHistory()->create(['old_status' => $oldStatus, 'new_status' => $newStatus, 'changed_by' => $request->user()->id, 'reason' => $request->validated('reason')]);
            $logger->log('status_changed', 'workers', $worker, 'Worker status changed.', ['from' => $oldStatus, 'to' => $newStatus]);
        }
        return back()->with('success', 'Worker status updated.');
    }

    private function formData(Worker $worker): array
    {
        return [
            'worker' => $worker,
            'services' => Service::where('is_active', true)->orderBy('sort_order')->get(),
            'dutyTypes' => DutyType::where('is_active', true)->orderBy('sort_order')->get(),
            'eligibleUsers' => User::where('is_active', true)->whereHas('role', fn ($q) => $q->whereIn('slug', ['super-admin', 'admin', 'staff']))->orderBy('name')->get(),
            'availabilityStatuses' => AvailabilityStatus::cases(), 'workerStatuses' => WorkerStatus::cases(),
        ];
    }
}
