<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    /**
     * Verify reCAPTCHA token
     *
     * @throws ValidationException
     */
    private function verifyRecaptcha(): void
    {
        $recaptchaSecret = env('RECAPTCHA_SECRET_KEY');
        
        // If reCAPTCHA is configured, it's required
        if ($recaptchaSecret) {
            if (!$this->recaptcha_token) {
                throw ValidationException::withMessages([
                    'email' => 'reCAPTCHA verification is required. Please verify that you are not a robot.',
                ]);
            }

            try {
                $response = Http::withOptions(['force_ip_resolve' => 'v4'])
                    ->asForm()
                    ->post('https://www.google.com/recaptcha/api/siteverify', [
                        'secret' => $recaptchaSecret,
                        'response' => $this->recaptcha_token,
                        'remoteip' => $this->ip(),
                    ]);

                $result = $response->json();

                if (!($result['success'] ?? false)) {
                    Log::warning('reCAPTCHA verification failed for login', [
                        'email' => $this->email,
                        'error_codes' => $result['error-codes'] ?? [],
                    ]);

                    throw ValidationException::withMessages([
                        'email' => 'reCAPTCHA verification failed. Please try again.',
                    ]);
                }

                Log::info('Admin login reCAPTCHA verified', [
                    'email' => $this->email,
                    'score' => $result['score'] ?? null,
                ]);
            } catch (\Exception $e) {
                Log::error('reCAPTCHA verification error during login: ' . $e->getMessage());
                throw ValidationException::withMessages([
                    'email' => 'Unable to verify reCAPTCHA. Please try again.',
                ]);
            }
        }
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Verify reCAPTCHA before attempting authentication
        $this->verifyRecaptcha();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $lockoutDuration = (int) \App\Models\SystemSetting::get('lockout_duration', 1) * 60;
            RateLimiter::hit($this->throttleKey(), $lockoutDuration);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        // Enforcement of Domain Isolation for Login
        if (tenant()) {
            // We are on a tenant domain
            if ($user->is_admin) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Super Admins are restricted to the central dashboard. Please login at ' . config('app.url'),
                ]);
            }
        } else {
            // We are on the central domain
            if (! $user->is_admin) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Clinic staff and owners must login through their specific clinic portal.',
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) \App\Models\SystemSetting::get('max_login_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
