<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use ZipArchive;

class FileUpdateService
{
    /**
     * Download and apply a code update from a ZIP URL.
     */
    public function updateFromZip(string $zipUrl, string $version): bool
    {
        $tempPath = storage_path('app/updates');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $zipFile = $tempPath . "/update_{$version}.zip";
        $extractPath = $tempPath . "/extract_{$version}";

        try {
            Log::info("Starting download of update ZIP for version {$version} from {$zipUrl}");
            
            // 1. Download ZIP
            $response = Http::timeout(300)->get($zipUrl);
            if (!$response->successful()) {
                throw new \Exception("Failed to download update ZIP: " . $response->status());
            }
            file_put_contents($zipFile, $response->body());

            // 2. Extract ZIP
            $zip = new ZipArchive;
            if ($zip->open($zipFile) === true) {
                if (!is_dir($extractPath)) {
                    mkdir($extractPath, 0755, true);
                }
                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                throw new \Exception("Failed to open update ZIP file.");
            }

            // GitHub ZIPs usually have a top-level directory like "repo-name-commit/"
            $subDirs = File::directories($extractPath);
            $sourcePath = !empty($subDirs) ? $subDirs[0] : $extractPath;

            // 3. Apply updates (overwrite files)
            $this->applyFiles($sourcePath, base_path());

            Log::info("Successfully applied file updates for version {$version}");

            // 4. Cleanup
            File::deleteDirectory($extractPath);
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("File update failed for version {$version}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recursively copy files from source to destination, avoiding sensitive files.
     */
    protected function applyFiles(string $source, string $destination): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            // Skip protected files and directories
            if ($this->isProtected($relativePath)) {
                continue;
            }

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                // Ensure parent directory exists
                $parentDir = dirname($targetPath);
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * Check if a file or directory path should be protected from updates.
     */
    protected function isProtected(string $path): bool
    {
        $protected = [
            '.env',
            'storage',
            'public/storage',
            'bootstrap/cache',
            '.git',
            'vendor', // Usually updated via composer, but for non-techy we might need to include it?
                      // Actually, for non-techy, we should probably include vendor in the ZIP if possible,
                      // or run composer install. But since this is a GitHub ZIP, it won't have vendor.
        ];

        foreach ($protected as $p) {
            if ($path === $p || str_starts_with($path, $p . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
