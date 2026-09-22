<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::with('role')->latest()->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User, 'roles' => $this->availableRoles()]);
    }

    public function store(StoreUserRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $role = Role::findOrFail($request->integer('role_id'));
        abort_if($role->slug === 'super-admin' && ! $request->user()->hasRole('super-admin'), 403);

        $user = User::create($request->safe()->except('password_confirmation') + [
            'password' => $request->string('password')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);
        $logger->log('created', 'users', $user, 'User created.', ['role' => $role->slug]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->guardSuperAdmin($user);

        return view('admin.users.form', ['user' => $user, 'roles' => $this->availableRoles()]);
    }

    public function update(UpdateUserRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        $this->guardSuperAdmin($user);
        $newRole = Role::findOrFail($request->integer('role_id'));
        abort_if($newRole->slug === 'super-admin' && ! $request->user()->hasRole('super-admin'), 403);
        abort_if($request->boolean('is_active') !== $user->is_active && ! $request->user()->hasPermission('users.activate'), 403);

        $wouldRemoveLastSuperAdmin = $user->hasRole('super-admin')
            && (! $request->boolean('is_active') || $newRole->slug !== 'super-admin')
            && User::where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))->count() <= 1;

        if ($wouldRemoveLastSuperAdmin) {
            return back()->withErrors(['is_active' => 'The last active Super Admin cannot be deactivated or reassigned.'])->withInput();
        }

        $data = $request->safe()->except(['password', 'password_confirmation']);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->filled('password')) {
            $data['password'] = $request->string('password')->toString();
        }
        $user->update($data);
        $logger->log('updated', 'users', $user, 'User updated.', ['role' => $newRole->slug]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    private function guardSuperAdmin(User $user): void
    {
        abort_if($user->hasRole('super-admin') && ! request()->user()->hasRole('super-admin'), 403);
    }

    private function availableRoles()
    {
        return Role::where('is_active', true)
            ->when(! request()->user()->hasRole('super-admin'), fn ($query) => $query->where('slug', '!=', 'super-admin'))
            ->orderBy('name')->get();
    }
}
