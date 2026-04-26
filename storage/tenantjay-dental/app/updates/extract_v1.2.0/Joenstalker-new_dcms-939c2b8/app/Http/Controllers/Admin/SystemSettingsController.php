<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BackupJob;
use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\PendingRegistration;
use App\Models\SystemSetting;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SystemSettingsController extends Controller
{
    /**
     * Display system settings page.
     */
    public function index(GoogleDriveService $driveService)
    {
        $groupedSettings = SystemSetting::getGroupedSettings();

        $backupLogs = BackupLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'status' => $log->status,
                    'started_at' => $log->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $log->completed_at?->format('Y-m-d H:i:s'),
                    'file_size' => $log->formatted_file_size,
                    'duration' => $log->duration,
                    'error_message' => $log->error_message,
                ];
            });

        $lastBackup = BackupLog::successful()->latest('completed_at')->first();

        return Inertia::render('Admin/SystemSettings/Index', [
            'settings' => $groupedSettings,
            'backupData' => [
                'settings' => [
                    'auto_backup_enabled' => SystemSetting::get('auto_backup_enabled', false),
                    'backup_frequency' => SystemSetting::get('backup_frequency', 'daily'),
                    'backup_time' => SystemSetting::get('backup_time', '02:00'),
                    'backup_retention_days' => SystemSetting::get('backup_retention_days', 7),
                ],
                'google_drive_connected' => $driveService->isConnected(),
                'drive_connection_type' => $driveService->usesServiceAccount() ? 'service_account' : 'oauth',
                'backup_logs' => $backupLogs,
                'last_backup' => $lastBackup ? [
                    'completed_at' => $lastBackup->completed_at->format('Y-m-d H:i:s'),
                    'status' => $lastBackup->status,
                    'file_size' => $lastBackup->formatted_file_size,
                ] : null,
            ],
        ]);
    }

    /**
     * Update system settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $settings = $validated['settings'];

        $this->validateConstrainedSettings($settings);

        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();

            if ($setting) {
                $setting->value = self::encodeValue($value, $setting->type);
                $setting->save();
            }
        }

        // Retroactively update pending registration timeouts if this setting was changed
        $this->syncPendingRegistrationsTimeouts($settings);

        // Clear config cache after updating settings
        $this->clearConfigCache();

        AuditLog::record(
            'settings_updated',
            'Updated multiple system settings.',
            'SystemSetting',
            null,
            ['setting_keys' => array_keys($settings)]
        );

        return redirect()->back()->with('success', 'System settings updated successfully.');
    }

    /**
     * Update settings by group.
     */
    public function updateByGroup(Request $request, string $group)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $settings = $validated['settings'];
        $this->validateConstrainedSettings($settings);
        $updated = 0;

        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)
                ->where('group', $group)
                ->first();

            if ($setting) {
                $setting->value = self::encodeValue($value, $setting->type);
                $setting->save();
                $updated++;
            }
        }

        // Retroactively update pending registration timeouts if this setting was changed
        $this->syncPendingRegistrationsTimeouts($settings);

        // Clear config cache after updating settings
        $this->clearConfigCache();

        AuditLog::record(
            'settings_updated',
            "Updated {$updated} settings in group '{$group}'.",
            'SystemSetting',
            null,
            ['group' => $group, 'count' => $updated]
        );

        return redirect()->back()->with('success', "{$updated} settings updated successfully.");
    }

    /**
     * Upload platform logo.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        $logo = $request->file('logo');

        // Delete old logo if exists
        $oldLogo = SystemSetting::get('platform_logo');
        if ($oldLogo && Storage::disk('public')->exists('logos/'.$oldLogo)) {
            Storage::disk('public')->delete('logos/'.$oldLogo);
        }

        // Generate unique filename
        $filename = 'logo_'.time().'.'.$logo->getClientOriginalExtension();

        // Store the file
        $path = $logo->storeAs('logos', $filename, 'public');

        // Update the setting
        $setting = SystemSetting::where('key', 'platform_logo')->first();
        if ($setting) {
            $setting->value = $filename;
            $setting->save();
        }

        $this->clearConfigCache();

        AuditLog::record(
            'logo_updated',
            'Updated platform logo.',
            'SystemSetting',
            'platform_logo',
            ['filename' => $filename]
        );

        return redirect()->back()->with('success', 'Logo uploaded successfully.');
    }

    /**
     * Delete platform logo.
     */
    public function deleteLogo()
    {
        $logo = SystemSetting::get('platform_logo');

        if ($logo && Storage::disk('public')->exists('logos/'.$logo)) {
            Storage::disk('public')->delete('logos/'.$logo);
        }

        $setting = SystemSetting::where('key', 'platform_logo')->first();
        if ($setting) {
            $setting->value = null;
            $setting->save();
        }

        $this->clearConfigCache();

        AuditLog::record(
            'logo_deleted',
            'Deleted platform logo.',
            'SystemSetting',
            'platform_logo'
        );

        return redirect()->back()->with('success', 'Logo deleted successfully.');
    }

    /**
     * Toggle a specific setting.
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        $setting = SystemSetting::where('key', $validated['key'])->first();

        if (! $setting) {
            return redirect()->back()->with('error', 'Setting not found.');
        }

        $currentValue = self::castValue($setting->value, $setting->type);
        $setting->value = self::encodeValue(! $currentValue, $setting->type);
        $setting->save();

        // Clear config cache after updating settings
        $this->clearConfigCache();

        AuditLog::record(
            'setting_toggled',
            "Toggled setting '{$validated['key']}' to ".($setting->value === 'true' ? 'enabled' : 'disabled').'.',
            'SystemSetting',
            $setting->id,
            ['key' => $validated['key'], 'value' => $setting->value]
        );

        return redirect()->back()->with('success', 'Setting toggled successfully.');
    }

    /**
     * Get settings for API (JSON response).
     */
    public function apiIndex()
    {
        $settings = SystemSetting::getGroupedSettings();

        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Get a single setting value.
     */
    public function apiShow(string $key)
    {
        $value = SystemSetting::get($key);

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Get backup settings and logs for the UI.
     */
    public function backupIndex(GoogleDriveService $driveService)
    {
        $settings = [
            'google_drive_connected' => $driveService->isConnected(),
            'auto_backup_enabled' => SystemSetting::get('auto_backup_enabled', false),
            'backup_frequency' => SystemSetting::get('backup_frequency', 'daily'),
            'backup_time' => SystemSetting::get('backup_time', '02:00'),
            'backup_retention_days' => SystemSetting::get('backup_retention_days', 7),
        ];

        $backupLogs = BackupLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'status' => $log->status,
                    'started_at' => $log->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $log->completed_at?->format('Y-m-d H:i:s'),
                    'file_size' => $log->formatted_file_size,
                    'duration' => $log->duration,
                    'error_message' => $log->error_message,
                ];
            });

        $lastBackup = BackupLog::successful()->latest('completed_at')->first();

        return response()->json([
            'settings' => $settings,
            'drive_connection_type' => $driveService->usesServiceAccount() ? 'service_account' : 'oauth',
            'backup_logs' => $backupLogs,
            'last_backup' => $lastBackup ? [
                'completed_at' => $lastBackup->completed_at->format('Y-m-d H:i:s'),
                'status' => $lastBackup->status,
                'file_size' => $lastBackup->formatted_file_size,
            ] : null,
        ]);
    }

    /**
     * Run manual backup.
     */
    public function runBackup(GoogleDriveService $driveService)
    {
        if (! $driveService->isConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'Google Drive not connected. Please connect Google Drive first.',
            ], 400);
        }

        try {
            // Dispatch backup job
            BackupJob::dispatch(true); // true = upload to drive
        } catch (\Throwable $e) {
            Log::error('Manual backup dispatch failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup failed to start: '.$e->getMessage(),
            ], 500);
        }

        AuditLog::record(
            'backup_manual',
            'Manual backup initiated by admin.',
            'BackupLog',
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Backup started successfully. Check the status in a few minutes.',
        ]);
    }

    /**
     * Update backup settings.
     */
    public function updateBackupSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_backup_enabled' => 'boolean',
            'backup_frequency' => 'in:daily,weekly',
            'backup_time' => 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'backup_retention_days' => 'integer|min:1|max:365',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value);
        }

        $this->clearConfigCache();

        AuditLog::record(
            'backup_settings_updated',
            'Backup settings updated.',
            'SystemSetting',
            null,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Backup settings updated successfully.',
        ]);
    }

    /**
     * Encode value for storage.
     */
    private static function encodeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Cast value based on type.
     */
    private static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Clear config cache after updating settings.
     */
    private function clearConfigCache(): void
    {
        try {
            Cache::forget('system_settings_all');
            \Artisan::call('config:clear');
        } catch (\Exception $e) {
            // Silently fail if cache clearing fails
        }
    }

    /**
     * Retroactively update timeouts for pending registrations if the setting was changed.
     */
    private function syncPendingRegistrationsTimeouts(array $settings): void
    {
        if (isset($settings['pending_timeout_default_minutes'])) {
            $newTimeout = (int) $settings['pending_timeout_default_minutes'];

            PendingRegistration::where('status', PendingRegistration::STATUS_PENDING)
                ->get()
                ->each(function ($reg) use ($newTimeout) {
                    $reg->pending_timeout_minutes = $newTimeout;
                    // Recalculate expires_at based on creation time, not current time
                    $reg->expires_at = $reg->created_at->copy()->addMinutes($newTimeout);
                    $reg->save();
                });
        }
    }

    /**
     * Validate constrained settings to prevent unsafe values.
     */
    private function validateConstrainedSettings(array $settings): void
    {
        $ruleMap = [
            'session_lifetime' => ['integer', 'min:5', 'max:1440'],
            'session_expire_on_close' => ['boolean'],
            'session_encrypt' => ['boolean'],
            'remember_me_duration' => ['integer', 'min:60', 'max:525600'],
            'max_login_attempts' => ['integer', 'min:1', 'max:100'],
            'lockout_duration' => ['integer', 'min:1', 'max:1440'],
            'password_reset_expiry' => ['integer', 'min:5', 'max:1440'],
            'pending_timeout_default_minutes' => ['integer', 'min:1', 'max:525600'],
            'pending_refund_timer_minutes' => ['integer', 'min:1', 'max:525600'],
            'pending_reminder_minutes_before' => ['integer', 'min:1', 'max:525600'],
            'pending_auto_approve_minutes' => ['integer', 'min:1', 'max:525600'],
            'pending_reminder_global_enabled' => ['boolean'],
            'pending_auto_approve_enabled' => ['boolean'],
            'pending_refund_timer_enabled' => ['boolean'],
        ];

        $rules = [];
        $payload = [];

        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $ruleMap)) {
                $rules[$key] = $ruleMap[$key];
                $payload[$key] = $value;
            }
        }

        if (! empty($rules)) {
            Validator::make($payload, $rules)->validate();
        }
    }
}
