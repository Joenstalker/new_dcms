<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ResetPasswordPageController extends Controller
{
    /**
     * Display the reset password page with modal
     */
    public function show($token)
    {
        $tenant = tenant();
        $email = request()->query('email');

        return Inertia::render('Tenant/Auth/ResetPasswordPage', [
            'token' => $token,
            'email' => $email,
            'tenant' => $tenant,
        ]);
    }
}
