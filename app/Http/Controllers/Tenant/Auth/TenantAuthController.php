<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\Client;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\Tenant\PasswordResetCode;
use Carbon\Carbon;

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

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'redirect' => route('tenant.dashboard'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'The provided credentials do not match our records.',
        ], 422);
    }

    /**
     * Send password reset code (6 digits)
     */
    public function sendResetCode(Request $request)
    {
        // Verify reCAPTCHA
        $captchaToken = $request->input('g-recaptcha-response');
        if (!$this->verifyRecaptcha($captchaToken)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'reCAPTCHA verification failed',
            ], 422);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Email address not found',
            ], 404);
        }

        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store code in password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $code, // We store the code in the token field
                'created_at' => Carbon::now()
            ]
        );

        // Get tenant branding
        $tenant = tenancy()->tenant;
        $tenantName = $tenant->name ?? 'Clinic Portal';
        $brandingColor = $tenant->branding_color ?? '#3b82f6';

        // Send Email
        try {
            Mail::to($user->email)->send(new PasswordResetCode($user, $code, $tenantName, $brandingColor));

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Verification code sent to your email',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reset code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to send email. Please try again later.',
            ], 500);
        }
    }

    /**
     * Verify the 6-digit code
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->first();

        if (!$record || (isset($record->created_at) && Carbon::parse($record->created_at)->addMinutes(60)->isPast())) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Invalid or expired verification code',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Code verified successfully',
        ]);
    }

    /**
     * Reset password using the code
     */
    public function resetWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|min:8|confirmed',
        ]);

        // Verify code again to be sure
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->first();

        if (!$record || (isset($record->created_at) && Carbon::parse($record->created_at)->addMinutes(60)->isPast())) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Session expired. Please request a new code.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        // Update password
        $user->password = bcrypt($request->password);
        $user->save();

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Password has been successfully updated',
        ]);
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
