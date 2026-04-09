<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'googleClientId' => config('services.google.client_id'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // On central domain, redirect admins to admin dashboard
        Log::info('Login attempt', [
            'user' => $user->email,
            'is_admin' => $user->is_admin,
            'tenant' => tenant('id'),
            'domain' => request()->getHost()
        ]);

        if (tenant()) {
            $request->session()->put([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) tenant()->getTenantKey(),
                'tenant_authenticated_user_id' => (int) optional($request->user())->id,
            ]);

            Log::info('Redirecting to tenant dashboard');
            return redirect()->intended(route('tenant.dashboard', absolute: false));
        }

        // Admin logins should always start in Admin System mode.
        $request->session()->forget('tenant_authenticated');
        $request->session()->forget('tenant_authenticated_tenant_id');
        $request->session()->forget('tenant_authenticated_user_id');
        $request->session()->forget('tenant_preview_bootstrap');
        $request->session()->forget('tenant_preview_active');

        Log::info('Redirecting to central dashboard');
        return redirect()->intended(route('admin.dashboard', absolute: false))
            ->with('success', 'Admin login successful! Welcome back.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $isCentral = !tenant();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Redirect admin to the landing page after logout
        if ($isCentral) {
            return redirect()->route('central.home');
        }

        return redirect()->route('tenant.landing');
    }
}
