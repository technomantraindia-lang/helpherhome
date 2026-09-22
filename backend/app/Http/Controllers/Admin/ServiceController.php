<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', ['services' => Service::orderBy('sort_order')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service]);
    }

    public function store(StoreServiceRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $service = Service::create($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $logger->log('created', 'services', $service, 'Service created.');

        return redirect()->route('admin.services.index')->with('success', 'Service created successfully.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', compact('service'));
    }

    public function update(UpdateServiceRequest $request, Service $service, ActivityLogger $logger): RedirectResponse
    {
        $service->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $logger->log('updated', 'services', $service, 'Service updated.');

        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully.');
    }
}
