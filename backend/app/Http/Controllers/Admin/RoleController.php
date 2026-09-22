<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', ['roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get()]);
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => Permission::orderBy('module')->orderBy('name')->get()->groupBy(fn ($permission) => $permission->module ?: 'Other'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, ActivityLogger $logger): RedirectResponse
    {
        $role->update(['description' => $request->validated('description')]);
        $role->permissions()->sync($request->validated('permissions', []));
        $logger->log('updated', 'roles', $role, 'Role permissions updated.');

        return redirect()->route('admin.roles.index')->with('success', 'Role permissions updated successfully.');
    }
}
