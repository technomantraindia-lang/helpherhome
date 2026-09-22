<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDutyTypeRequest;
use App\Http\Requests\UpdateDutyTypeRequest;
use App\Models\DutyType;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DutyTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.duty-types.index', ['dutyTypes' => DutyType::orderBy('sort_order')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.duty-types.form', ['dutyType' => new DutyType]);
    }

    public function store(StoreDutyTypeRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $dutyType = DutyType::create($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $logger->log('created', 'duty-types', $dutyType, 'Duty type created.');

        return redirect()->route('admin.duty-types.index')->with('success', 'Duty type created successfully.');
    }

    public function edit(DutyType $dutyType): View
    {
        return view('admin.duty-types.form', compact('dutyType'));
    }

    public function update(UpdateDutyTypeRequest $request, DutyType $dutyType, ActivityLogger $logger): RedirectResponse
    {
        $dutyType->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $logger->log('updated', 'duty-types', $dutyType, 'Duty type updated.');

        return redirect()->route('admin.duty-types.index')->with('success', 'Duty type updated successfully.');
    }
}
