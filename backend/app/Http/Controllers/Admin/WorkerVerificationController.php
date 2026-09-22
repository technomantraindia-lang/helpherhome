<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkerVerificationRequest;
use App\Models\Worker;
use App\Models\WorkerVerification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $workers = Worker::with('verification')->when($request->filled('status'), fn ($q) => $q->whereHas('verification', fn ($v) => $v->where('overall_verification_status', $request->input('status'))))->orderBy('name')->paginate(25)->withQueryString();
        return view('admin.worker-verifications.index', compact('workers'));
    }

    public function edit(Worker $worker): View
    {
        $verification = $worker->verification()->firstOrCreate();
        return view('admin.worker-verifications.edit', ['worker' => $worker, 'verification' => $verification, 'statuses' => VerificationStatus::cases()]);
    }

    public function update(UpdateWorkerVerificationRequest $request, Worker $worker, ActivityLogger $logger): RedirectResponse
    {
        $verification = $worker->verification()->firstOrCreate();
        $data = $request->validated();
        $statuses = [$data['document_verification_status'], $data['police_verification_status'], $data['background_verification_status']];
        $data['overall_verification_status'] = WorkerVerification::overallFor($statuses)->value;
        foreach (['document', 'police', 'background'] as $type) {
            $field = $type.'_verification_status';
            $data[$type.'_verified_by'] = $data[$field] === VerificationStatus::Pending->value ? null : $request->user()->id;
            if ($data[$field] !== VerificationStatus::Pending->value && empty($data[$type.'_verification_date'])) $data[$type.'_verification_date'] = today();
        }
        $verification->update($data);
        $logger->log('updated', 'worker-verification', $verification, 'Worker verification updated.', ['overall_status' => $data['overall_verification_status']]);
        return redirect()->route('admin.workers.show', $worker)->with('success', 'Worker verification updated.');
    }
}
