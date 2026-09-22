<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AvailabilityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkerAvailabilityRequest;
use App\Models\Worker;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerAvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $workers = Worker::with('services:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('availability_status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', '%'.$request->input('search').'%')->orWhere('worker_code', 'like', '%'.$request->input('search').'%')))
            ->orderBy('availability_status')->orderBy('name')->paginate(25)->withQueryString();
        return view('admin.worker-availability.index', ['workers' => $workers, 'statuses' => AvailabilityStatus::cases()]);
    }

    public function update(UpdateWorkerAvailabilityRequest $request, Worker $worker, ActivityLogger $logger): RedirectResponse
    {
        $old = $worker->availability_status->value;
        $new = $request->validated('availability_status');
        $worker->update(['availability_status' => $new, 'updated_by' => $request->user()->id]);
        $logger->log('availability_changed', 'workers', $worker, 'Worker availability changed.', ['from' => $old, 'to' => $new]);
        return back()->with('success', 'Availability updated.');
    }
}
