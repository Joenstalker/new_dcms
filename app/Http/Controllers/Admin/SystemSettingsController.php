<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    /**
     * Display system settings page.
     */
    public function index()
    {
        $groupedSettings = SystemSetting::getGroupedSettings();

        return Inertia::render('Admin/SystemSettings/Index', [
            'settings' => $groupedSettings,
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

        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();

            if ($setting) {
                $setting->value = self::encodeValue($value, $setting->type);
                $setting->save();
            }
        }

        // Clear config cache after updating settings
        $this->clearConfigCache();

        \App\Models\AuditLog::record(
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

        // Clear config cache after updating settings
        $this->clearConfigCache();

        \App\Models\AuditLog::record(
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
        if ($oldLogo && Storage::disk('public')->exists('logos/' . $oldLogo)) {
            Storage::disk('public')->delete('logos/' . $oldLogo);
        }

        // Generate unique filename
        $filename = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();

        // Store the file
        $path = $logo->storeAs('logos', $filename, 'public');

        // Update the setting
        $setting = SystemSetting::where('key', 'platform_logo')->first();
        if ($setting) {
            $setting->value = $filename;
            $setting->save();
        }

        $this->clearConfigCache();

        \App\Models\AuditLog::record(
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

        if ($logo && Storage::disk('public')->exists('logos/' . $logo)) {
            Storage::disk('public')->delete('logos/' . $logo);
        }

        $setting = SystemSetting::where('key', 'platform_logo')->first();
        if ($setting) {
            $setting->value = null;
            $setting->save();
        }

        $this->clearConfigCache();

        \App\Models\AuditLog::record(
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

        if (!$setting) {
            return redirect()->back()->with('error', 'Setting not found.');
        }

        $currentValue = self::castValue($setting->value, $setting->type);
        $setting->value = self::encodeValue(!$currentValue, $setting->type);
        $setting->save();

        // Clear config cache after updating settings
        $this->clearConfigCache();

        \App\Models\AuditLog::record(
            'setting_toggled',
            "Toggled setting '{$validated['key']}' to " . ($setting->value === 'true' ? 'enabled' : 'disabled') . ".",
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
     * Encode value for storage.
     */
    private static function encodeValue(mixed $value, string $type): string
    {
        return match ($type) {
                'boolean' => $value ? 'true' : 'false',
                'json' => json_encode($value),
                default => (string)$value,
            };
    }

    /**
     * Cast value based on type.
     */
    private static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int)$value,
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
            \Artisan::call('config:clear');
        }
        catch (\Exception $e) {
        // Silently fail if cache clearing fails
        }
    }
}
