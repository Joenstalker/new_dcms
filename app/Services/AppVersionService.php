<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AppVersionService
{
    /**
     * Get the current application version based on git commit count.
     */
    public static function getVersion(): string
    {
        return Cache::remember('app_version', 3600, function () {
            try {
                // Determine the base path of the application
                $basePath = base_path();
                
                // Execute git command to get total commit count
                // 2>&1 ensures we capture stderr to detect if git fails
                $output = shell_exec("cd {$basePath} && git rev-list --count HEAD 2>&1");
                
                if ($output) {
                    $commitCount = trim($output);
                    
                    // Simple check to ensure it's a number, not an error string like "fatal: not a git repo"
                    if (is_numeric($commitCount)) {
                        return 'v1.0.' . $commitCount;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to get app version: ' . $e->getMessage());
            }

            return '1.0.0-dev'; // Fallback if git is unavailable
        });
    }

    /**
     * Clear the cached version. Call this during deployments or post-updates.
     */
    public static function clearCache(): void
    {
        Cache::forget('app_version');
    }
}
