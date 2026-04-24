<?php

namespace App\Services;

use App\Models\SystemSetting;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected Client $client;

    protected string $folderId;

    protected bool $usesServiceAccount = false;

    public function __construct()
    {
        $this->client = new Client;
        $this->folderId = config('services.google_drive.folder_id');

        $serviceAccountJson = config('services.google_drive.service_account_json');
        $serviceAccountJsonPath = config('services.google_drive.service_account_json_path');
        $serviceAccountImpersonate = config('services.google_drive.service_account_impersonate');

        if ($serviceAccountJson || $serviceAccountJsonPath) {
            if ($serviceAccountJson) {
                $decoded = json_decode($serviceAccountJson, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $decoded = json_decode(base64_decode($serviceAccountJson), true);
                }

                if (is_array($decoded)) {
                    $this->client->setAuthConfig($decoded);
                }
            } else {
                $this->client->setAuthConfig($serviceAccountJsonPath);
            }

            if ($serviceAccountImpersonate) {
                $this->client->setSubject($serviceAccountImpersonate);
            }

            $this->usesServiceAccount = true;
        } else {
            $this->client->setClientId(config('services.google_drive.client_id'));
            $this->client->setClientSecret(config('services.google_drive.client_secret'));

            $redirectUri = config('services.google_drive.redirect_uri');
            if (empty($redirectUri)) {
                $redirectUri = route('admin.drive.callback', [], false);
            }

            $this->client->setRedirectUri($redirectUri);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');
        }

        $this->client->addScope(Drive::DRIVE_FILE);
    }

    /**
     * Get Google OAuth authorization URL
     */
    public function usesServiceAccount(): bool
    {
        return $this->usesServiceAccount;
    }

    public function getAuthUrl(): string
    {
        if ($this->usesServiceAccount()) {
            throw new \RuntimeException('Service account configured; no OAuth URL is required.');
        }

        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and store tokens
     */
    public function handleCallback(string $code): bool
    {
        if ($this->usesServiceAccount()) {
            return true;
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                Log::error('Google Drive OAuth error: '.$token['error']);

                return false;
            }

            // Store tokens securely
            SystemSetting::set('google_drive_access_token', Crypt::encrypt($token['access_token']));
            SystemSetting::set('google_drive_refresh_token', Crypt::encrypt($token['refresh_token']));
            SystemSetting::set('google_drive_token_expires', $token['expires_in'] + time());
            SystemSetting::set('google_drive_connected', true);

            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive callback error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Check if Google Drive is connected
     */
    public function isConnected(): bool
    {
        if ($this->usesServiceAccount()) {
            return true;
        }

        return SystemSetting::get('google_drive_connected', false);
    }

    /**
     * Refresh access token if expired
     */
    protected function refreshTokenIfNeeded(): bool
    {
        if ($this->usesServiceAccount()) {
            return true;
        }

        $expiresAt = SystemSetting::get('google_drive_token_expires', 0);

        if (time() >= $expiresAt) {
            try {
                $refreshToken = Crypt::decrypt(SystemSetting::get('google_drive_refresh_token'));

                $this->client->refreshToken($refreshToken);
                $newToken = $this->client->getAccessToken();

                SystemSetting::set('google_drive_access_token', Crypt::encrypt($newToken['access_token']));
                SystemSetting::set('google_drive_token_expires', $newToken['expires_in'] + time());

                return true;
            } catch (\Exception $e) {
                Log::error('Token refresh failed: '.$e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * Upload file to Google Drive
     */
    public function uploadFile(string $filePath, string $fileName, string $mimeType = 'application/zip'): ?string
    {
        if (! $this->isConnected()) {
            throw new \Exception('Google Drive not connected');
        }

        if (! $this->refreshTokenIfNeeded()) {
            throw new \Exception('Failed to refresh token');
        }

        try {
            if ($this->usesServiceAccount()) {
                if ($this->client->getAccessToken() === null || $this->client->isAccessTokenExpired()) {
                    $this->client->fetchAccessTokenWithAssertion();
                }
            } else {
                $accessToken = Crypt::decrypt(SystemSetting::get('google_drive_access_token'));
                $this->client->setAccessToken($accessToken);
            }

            $service = new Drive($this->client);

            $file = new DriveFile;
            $file->setName($fileName);
            $file->setParents([$this->folderId]);

            $result = $service->files->create(
                $file,
                [
                    'data' => file_get_contents($filePath),
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart',
                    'fields' => 'id',
                ]
            );

            return $result->id;
        } catch (\Exception $e) {
            Log::error('Google Drive upload error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Disconnect Google Drive
     */
    public function disconnect(): bool
    {
        if ($this->usesServiceAccount()) {
            return true;
        }

        SystemSetting::set('google_drive_connected', false);
        SystemSetting::set('google_drive_access_token', null);
        SystemSetting::set('google_drive_refresh_token', null);
        SystemSetting::set('google_drive_token_expires', null);

        return true;
    }
}
