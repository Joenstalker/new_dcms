<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    private function verifiedRedirect(): string
    {
        if (tenant()) {
            return route('tenant.dashboard', absolute: false);
        }

        return route('admin.dashboard', absolute: false);
    }

    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended($this->verifiedRedirect())
                    : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
