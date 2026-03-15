<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:10|max:2000',
            'recaptcha_token' => 'nullable|string',
        ]);

        // Verify reCAPTCHA
        $recaptchaScore = null;
        $recaptchaSecret = env('RECAPTCHA_SECRET_KEY');
        if ($recaptchaSecret && $request->recaptcha_token) {
            try {
                $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $recaptchaSecret,
                    'response' => $request->recaptcha_token,
                    'remoteip' => $request->ip(),
                ]);

                $result = $response->json();
                $recaptchaScore = $result['score'] ?? null;

                if (!($result['success'] ?? false)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'reCAPTCHA verification failed. Please try again.',
                    ], 422);
                }
            } catch (\Exception $e) {
                Log::warning('reCAPTCHA verification error: ' . $e->getMessage());
            }
        }

        // Store in database
        ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
            'status' => 'unread',
            'ip_address' => $request->ip(),
            'recaptcha_score' => $recaptchaScore,
        ]);

        Log::info('Contact form submitted', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully!',
        ]);
    }
}
