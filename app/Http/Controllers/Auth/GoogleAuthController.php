<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Handle Google Login from Modal (Token Verification)
     */
    public function handleGoogleLogin(Request $request)
    {
        try {
            $request->validate([
                'credential' => 'required|string', // JWT from Google GIS
            ]);

            // For Google One Tap / GIS, we receive an ID Token (JWT).
            // Socialite's userFromToken is for access tokens.
            // We can use the Socialite Google driver to get user details from the ID token 
            // by using the stateless() and userFromToken() if we have an access token, 
            // but for ID tokens, we should verify it.
            
            // For now, we will use a simpler approach or the Google API client if available.
            // If the user has Socialite, we can try to use it, but most reliable for ID tokens:
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->credential);

            if (!$googleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify Google account.'
                ], 401);
            }

            // Detect if we are on a tenant or central domain
            $isTenant = tenant() !== null;
            
            // check if user exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Google account is not registered. Please contact your administrator.'
                ], 403);
            }

            // If central domain, ensure user is an admin
            if (!$isTenant && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have administrative access.'
                ], 403);
            }

            // Log the user in
            Auth::login($user, true);

            $request->session()->regenerate();

            // Redirect based on domain
            $redirect = $isTenant 
                ? route('tenant.dashboard', [], false) 
                : route('admin.dashboard', [], false);

            return response()->json([
                'success' => true,
                'redirect' => $redirect,
            ]);

        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during Google authentication.'
            ], 500);
        }
    }
}
