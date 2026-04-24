<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleDriveController extends Controller
{
    public function __construct(
        protected GoogleDriveService $googleDriveService
    ) {}

    /**
     * Redirect to Google OAuth
     */
    public function connect(): RedirectResponse
    {
        if ($this->googleDriveService->usesServiceAccount()) {
            return redirect('/admin/system-settings')
                ->with('success', 'Google Drive is configured through service account credentials; OAuth sign-in is not required.');
        }

        return redirect($this->googleDriveService->getAuthUrl());
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request): RedirectResponse
    {
        $code = $request->get('code');

        if (! $code) {
            return redirect('/admin/system-settings')
                ->with('error', 'Google Drive authorization failed - no code received');
        }

        try {
            $success = $this->googleDriveService->handleCallback($code);

            if ($success) {
                return redirect('/admin/system-settings')
                    ->with('success', 'Google Drive connected successfully');
            } else {
                return redirect('/admin/system-settings')
                    ->with('error', 'Failed to connect Google Drive');
            }
        } catch (\Exception $e) {
            return redirect('/admin/system-settings')
                ->with('error', 'Google Drive connection error: '.$e->getMessage());
        }
    }

    /**
     * Disconnect Google Drive
     */
    public function disconnect(): RedirectResponse
    {
        try {
            $this->googleDriveService->disconnect();

            return redirect('/admin/system-settings')
                ->with('success', 'Google Drive disconnected successfully');
        } catch (\Exception $e) {
            return redirect('/admin/system-settings')
                ->with('error', 'Failed to disconnect Google Drive: '.$e->getMessage());
        }
    }

    /**
     * Get Google Drive connection status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'connected' => $this->googleDriveService->isConnected(),
        ]);
    }
}
