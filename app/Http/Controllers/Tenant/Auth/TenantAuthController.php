<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TenantAuthController extends Controller
{
    /**
     * Handle tenant login from modal
     */
    public function store(Request $request)
    {
        // Verify reCAPTCHA
        $captchaToken = $request->input('g-recaptcha-response');
        if (!$this->verifyRecaptcha($captchaToken)) {
            return response()->json([
                'success' => false,
                'message' => 'reCAPTCHA verification failed',
            ], 422);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login
        if (Auth::attempt(
            ['email' => $request->email, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => route('tenant.dashboard', absolute: false),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password',
        ], 401);
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        // Verify reCAPTCHA
        $captchaToken = $request->input('g-recaptcha-response');
        if (!$this->verifyRecaptcha($captchaToken)) {
            return response()->json([
                'success' => false,
                'message' => 'reCAPTCHA verification failed',
            ], 422);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        // Send reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Reset link sent to your email',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send reset link. Email may not exist.',
        ], 404);
    }

    /**
     * Reset password from modal
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successful',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to reset password',
        ], 400);
    }

    /**
     * Verify reCAPTCHA token
     */
    private function verifyRecaptcha($token): bool
    {
        try {
            $client = new Client();
            $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => env('RECAPTCHA_SECRET_KEY'),
                    'response' => $token,
                ],
            ]);

            $body = json_decode((string) $response->getBody());
            
            // Verify threshold if score is present (reCAPTCHA v3), else just success (v2)
            if (isset($body->score)) {
                return $body->success && $body->score >= 0.5;
            }

            return $body->success;
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification failed: ' . $e->getMessage());
            return false;
        }
    }
}
