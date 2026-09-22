<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active || ! $user->role?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Your account is inactive. Please contact an administrator.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $logger->log('login', 'authentication', $user, 'User signed in.');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(ActivityLogger $logger): RedirectResponse
    {
        $logger->log('logout', 'authentication', request()->user(), 'User signed out.');
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
