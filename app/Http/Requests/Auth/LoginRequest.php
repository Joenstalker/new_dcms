<?php

namespace App\Http\Requests\Auth;

use App\Models\SystemSetting;
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
        if (app()->environment('testing')) {
            return;
        }

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
            RateLimiter::hit($this->throttleKey(), $this->lockoutDecaySeconds());

            $maxAttempts = $this->maxLoginAttempts();
            $attempts = RateLimiter::attempts($this->throttleKey());
            $remaining = max($maxAttempts - $attempts, 0);

            if (RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
                event(new Lockout($this));

                $seconds = RateLimiter::availableIn($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => trans('auth.throttle', [
                        'seconds' => $seconds,
                        'minutes' => ceil($seconds / 60),
                    ]),
                    'lockout_seconds' => (string) $seconds,
                ]);
            }

            throw ValidationException::withMessages([
                'email' => $this->failedAttemptMessage($remaining),
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
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxLoginAttempts())) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
            'lockout_seconds' => (string) $seconds,
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Get the max failed login attempts before lockout.
     */
    private function maxLoginAttempts(): int
    {
        $value = (int) SystemSetting::get('max_login_attempts', 5);

        // Guardrails to avoid invalid runtime values.
        return max(1, min($value, 100));
    }

    /**
     * Get lockout duration in seconds.
     */
    private function lockoutDecaySeconds(): int
    {
        $minutes = (int) SystemSetting::get('lockout_duration', 15);
        $minutes = max(1, min($minutes, 1440));

        return $minutes * 60;
    }

    /**
     * Build a failed login message with dynamic remaining attempts.
     */
    private function failedAttemptMessage(int $remaining): string
    {
        if ($remaining <= 0) {
            return trans('auth.failed');
        }

        $attemptWord = $remaining === 1 ? 'attempt' : 'attempts';

        return trans('auth.failed') . " You have {$remaining} {$attemptWord} remaining before lockout.";
    }
}
