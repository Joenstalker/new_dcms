# Distributed Update Mechanism - Implementation Guide & Pseudocode

This document provides detailed pseudocode and implementation examples for the distributed update mechanism.

---

## 1. Core Service: DistributedUpdateChecker

### Purpose
Detect new releases from GitHub and compare with local version.

### Pseudocode

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DistributedUpdateChecker
{
    /**
     * Check if a new update is available
     * 
     * @return array{
     *     hasUpdate: bool,
     *     current: string,
     *     latest: string|null,
     *     release: array|null,
     *     fromCache: bool,
     *     error?: string
     * }
     */
    public function check(): array
    {
        // Step 1: Get local version
        $currentVersion = $this->getLocalVersion();
        
        // Step 2: Try to fetch from GitHub
        $latestRelease = $this->fetchLatestFromGitHub();
        
        if ($latestRelease) {
            // Step 3: Store in cache for next time
            $this->cacheRelease($latestRelease);
            
            // Step 4: Compare versions
            $latestVersion = $latestRelease['tag_name'];
            $isNewer = $this->isNewerVersion($latestVersion, $currentVersion);
            
            return [
                'hasUpdate' => $isNewer,
                'current' => $currentVersion,
                'latest' => $latestVersion,
                'release' => $latestRelease,
                'fromCache' => false,
            ];
        }
        
        // GitHub failed, try fallback methods
        return $this->fallbackCheck($currentVersion);
    }
    
    /**
     * Get the current application version
     * 
     * Priority order:
     * 1. .env APP_VERSION
     * 2. config/app.php version
     * 3. Database latest installed
     * 4. Cache from last check
     * 5. Hardcoded default
     */
    private function getLocalVersion(): string
    {
        // Try .env
        if ($version = env('APP_VERSION')) {
            return $version;
        }
        
        // Try config
        if ($version = config('app.version')) {
            return $version;
        }
        
        // Try database (latest installed)
        $installed = \App\Models\SystemRelease::where('installed_at', '!=', null)
            ->latest('installed_at')
            ->value('version');
        
        if ($installed) {
            return $installed;
        }
        
        // Try cache
        $cached = Cache::get('app.version');
        if ($cached) {
            return $cached;
        }
        
        // Fallback
        return 'v1.0.0';
    }
    
    /**
     * Fetch latest release from GitHub API
     */
    private function fetchLatestFromGitHub(): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'DCMS-UpdateChecker/1.0',
            ])->timeout(5)
              ->connectTimeout(3)
              ->get('https://api.github.com/repos/Joenstalker/new_dcms/releases/latest');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::warning('GitHub API non-successful response', [
                'status' => $response->status(),
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch GitHub release', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Cache release data for fallback scenarios
     */
    private function cacheRelease(array $release): void
    {
        // In-memory cache (5 minutes)
        Cache::put('github_latest_release', $release, 300);
        
        // File-based cache (1 hour)
        $cacheDir = storage_path('framework/cache/releases');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/latest.json';
        file_put_contents($cacheFile, json_encode([
            'release' => $release,
            'cached_at' => now()->toIso8601String(),
        ]));
        
        // Cache the version string
        Cache::put('github_latest_version', $release['tag_name'], 3600);
    }
    
    /**
     * Compare two versions
     * 
     * Returns true if $latest is newer than $current
     */
    private function isNewerVersion(string $latest, string $current): bool
    {
        $latestClean = ltrim($latest, 'vV');
        $currentClean = ltrim($current, 'vV');
        
        $cmp = version_compare($latestClean, $currentClean, '>');
        return $cmp === 1;
    }
    
    /**
     * Fallback check when GitHub is unreachable
     */
    private function fallbackCheck(string $currentVersion): array
    {
        // Try in-memory cache
        $cached = Cache::get('github_latest_release');
        if ($cached) {
            return [
                'hasUpdate' => $this->isNewerVersion($cached['tag_name'], $currentVersion),
                'current' => $currentVersion,
                'latest' => $cached['tag_name'],
                'release' => $cached,
                'fromCache' => true,
            ];
        }
        
        // Try file-based cache
        $cacheFile = storage_path('framework/cache/releases/latest.json');
        if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 3600) {
            $data = json_decode(file_get_contents($cacheFile), true);
            return [
                'hasUpdate' => $this->isNewerVersion($data['release']['tag_name'], $currentVersion),
                'current' => $currentVersion,
                'latest' => $data['release']['tag_name'],
                'release' => $data['release'],
                'fromCache' => true,
            ];
        }
        
        // No cache available
        return [
            'hasUpdate' => false,
            'current' => $currentVersion,
            'latest' => null,
            'release' => null,
            'fromCache' => false,
            'error' => 'Unable to check for updates (no network, no cache)',
        ];
    }
}
```

---

## 2. Core Service: DistributedUpdateExecutor

### Purpose
Execute the update process safely with backup, migration, and rollback support.

### Pseudocode

```php
<?php

namespace App\Services;

use App\Models\SystemRelease;
use App\Models\UpdateHistory;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DistributedUpdateExecutor
{
    private BackupService $backupService;
    private DistributedUpdateChecker $checker;
    private array $executionLog = [];
    
    public function __construct(BackupService $backupService, DistributedUpdateChecker $checker)
    {
        $this->backupService = $backupService;
        $this->checker = $checker;
    }
    
    /**
     * Execute update for a specific release
     * 
     * @throws Exception
     * @return array{success: bool, version: string|null, duration: int, log: array}
     */
    public function execute(string $releaseVersion): array
    {
        $startTime = time();
        
        try {
            $this->log("Starting update to {$releaseVersion}");
            
            // Load release from database
            $release = SystemRelease::where('version', $releaseVersion)
                ->firstOrFail();
            
            // Step 1: Pre-flight checks
            $this->preFlightChecks($release);
            $this->log("✓ Pre-flight checks passed");
            
            // Step 2: Create backup
            $backupId = $this->backupService->createBackup($release->version);
            $this->log("✓ Backup created: {$backupId}");
            
            // Step 3: Download release
            $this->downloadRelease($release->version);
            $this->log("✓ Release downloaded and verified");
            
            // Step 4: Clear caches
            Artisan::call('optimize:clear');
            $this->log("✓ Caches cleared");
            
            // Step 5: Execute migrations if needed
            if ($release->requires_db_update) {
                $this->executeMigrations($release);
                $this->log("✓ Database migrations executed");
            }
            
            // Step 6: Verify update
            $this->verifyUpdate($release->version);
            $this->log("✓ Update verification passed");
            
            // Step 7: Record success
            $this->recordUpdateHistory(
                releaseVersion: $release->version,
                status: 'completed',
                backupId: $backupId,
                duration: time() - $startTime,
            );
            
            $this->log("✓ Update completed successfully");
            
            return [
                'success' => true,
                'version' => $release->version,
                'duration' => time() - $startTime,
                'log' => $this->executionLog,
            ];
            
        } catch (Exception $e) {
            // Automatic rollback
            $this->handleFailure($e, $backupId, $release->version ?? null, time() - $startTime);
            
            throw $e;
        }
    }
    
    /**
     * Pre-flight validation checks
     */
    private function preFlightChecks(SystemRelease $release): void
    {
        // Check disk space (need at least 500MB)
        $freeDiskSpace = disk_free_space('/');
        if ($freeDiskSpace < 500 * 1024 * 1024) {
            throw new Exception('Insufficient disk space (need 500MB, have ' . round($freeDiskSpace / 1024 / 1024) . 'MB)');
        }
        
        // Check required PHP extensions
        $required = ['pdo', 'pdo_mysql', 'json'];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("Missing required PHP extension: {$ext}");
            }
        }
        
        // Check database connectivity
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
        
        // Check git availability (for downloading)
        exec('git --version 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            throw new Exception('Git not available or not in PATH');
        }
    }
    
    /**
     * Download and verify the release
     */
    private function downloadRelease(string $version): void
    {
        $projectPath = base_path();
        
        // Use git to fetch and checkout the tag
        exec("cd {$projectPath} && git fetch origin {$version} 2>&1", $output, $exitCode);
        if ($exitCode !== 0) {
            throw new Exception('Failed to fetch release from git: ' . implode('\n', $output));
        }
        
        exec("cd {$projectPath} && git checkout {$version} 2>&1", $output, $exitCode);
        if ($exitCode !== 0) {
            throw new Exception('Failed to checkout release: ' . implode('\n', $output));
        }
        
        // Verify version file
        $versionFile = $projectPath . '/version.txt';
        if (file_exists($versionFile)) {
            $fileVersion = trim(file_get_contents($versionFile));
            if ($fileVersion !== $version) {
                throw new Exception("Version mismatch: expected {$version}, got {$fileVersion}");
            }
        }
    }
    
    /**
     * Execute tenant migrations
     */
    private function executeMigrations(SystemRelease $release): void
    {
        try {
            // Use Artisan to run migrations for all tenants
            $output = null;
            $exitCode = 0;
            
            exec('php artisan tenants:migrate 2>&1', $output, $exitCode);
            
            if ($exitCode !== 0) {
                throw new Exception('Migrations failed: ' . implode('\n', $output));
            }
            
            Log::info('Tenant migrations completed successfully', [
                'release' => $release->version,
                'output' => implode('\n', $output),
            ]);
            
        } catch (Exception $e) {
            Log::error('Tenant migration failed', [
                'release' => $release->version,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Verify that the update was successful
     */
    private function verifyUpdate(string $expectedVersion): void
    {
        // Read version from multiple sources
        $appVersion = env('APP_VERSION');
        $configVersion = config('app.version');
        
        // At least one should match
        if ($appVersion !== $expectedVersion && $configVersion !== $expectedVersion) {
            throw new Exception("Version verification failed: expected {$expectedVersion}");
        }
        
        // Check that release is marked as installed
        $release = SystemRelease::where('version', $expectedVersion)->first();
        if (!$release) {
            throw new Exception("Release record not found: {$expectedVersion}");
        }
    }
    
    /**
     * Handle update failure and perform rollback
     */
    private function handleFailure(Exception $error, ?string $backupId, ?string $version, int $duration): void
    {
        Log::error('Update execution failed', [
            'version' => $version,
            'error' => $error->getMessage(),
            'backup_id' => $backupId,
        ]);
        
        // Perform rollback
        if ($backupId) {
            try {
                $this->backupService->restoreBackup($backupId);
                $this->log("✓ Rollback completed using backup {$backupId}");
            } catch (Exception $e) {
                Log::critical('Rollback failed!', [
                    'backup_id' => $backupId,
                    'error' => $e->getMessage(),
                ]);
                $this->log("✗ CRITICAL: Rollback failed!");
            }
        }
        
        // Record failure in history
        $this->recordUpdateHistory(
            releaseVersion: $version,
            status: 'failed',
            backupId: $backupId,
            duration: $duration,
            errorMessage: $error->getMessage(),
        );
    }
    
    /**
     * Record update in history for audit trail
     */
    private function recordUpdateHistory(
        string $releaseVersion,
        string $status,
        ?string $backupId,
        int $duration,
        ?string $errorMessage = null
    ): void
    {
        UpdateHistory::create([
            'environment_id' => env('ENVIRONMENT_ID', gethostname()),
            'from_version' => $this->checker->check()['current'],
            'to_version' => $releaseVersion,
            'status' => $status,
            'backup_id' => $backupId,
            'duration_seconds' => $duration,
            'error_message' => $errorMessage,
            'executed_by' => auth()->user()->email ?? 'system',
            'log_output' => json_encode($this->executionLog),
        ]);
    }
    
    /**
     * Add message to execution log
     */
    private function log(string $message): void
    {
        $timestamp = now()->format('H:i:s');
        $this->executionLog[] = "[{$timestamp}] {$message}";
        Log::info($message);
    }
}
```

---

## 3. Core Service: BackupService

### Purpose
Create and manage database/code backups for safe rollback.

### Pseudocode

```php
<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupService
{
    /**
     * Create a backup of the current state
     * 
     * @return string Backup ID
     */
    public function createBackup(string $version): string
    {
        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $environmentId = env('ENVIRONMENT_ID', gethostname());
        $timestamp = now()->format('YmdHis');
        $backupId = "{$environmentId}_v{$version}_{$timestamp}";
        
        try {
            // Create database backup
            $this->backupDatabase($backupId, $backupDir);
            
            // Create code snapshot
            $this->createCodeSnapshot($backupId, $backupDir);
            
            // Create metadata file
            $this->createBackupMetadata($backupId, $backupDir, $version);
            
            Log::info("Backup created successfully: {$backupId}");
            
            return $backupId;
            
        } catch (Exception $e) {
            Log::error("Backup creation failed: {$e->getMessage()}");
            throw $e;
        }
    }
    
    /**
     * Backup the database
     */
    private function backupDatabase(string $backupId, string $backupDir): void
    {
        $dbName = config('database.connections.mysql.database');
        $dbHost = config('database.connections.mysql.host');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        
        $backupFile = "{$backupDir}/{$backupId}_db.sql";
        
        // Use mysqldump to create backup
        $cmd = "mysqldump " .
               "--user={$dbUser} " .
               "--password='{$dbPass}' " .
               "--host={$dbHost} " .
               "--single-transaction " .
               "--quick " .
               "{$dbName} > {$backupFile}";
        
        exec($cmd, $output, $exitCode);
        
        if ($exitCode !== 0) {
            throw new Exception('Database backup failed: ' . implode('\n', $output));
        }
        
        // Compress backup
        exec("gzip {$backupFile}");
    }
    
    /**
     * Create code snapshot
     */
    private function createCodeSnapshot(string $backupId, string $backupDir): void
    {
        $projectPath = base_path();
        $snapshotFile = "{$backupDir}/{$backupId}_code.tar.gz";
        
        // Create tarball of important directories
        $dirs = [
            'app/',
            'bootstrap/',
            'config/',
            'database/migrations/',
            'resources/',
            'routes/',
            '.env',
            'composer.json',
        ];
        
        $dirList = implode(' ', array_map(fn($d) => "--exclude={$d}/* " . $d, $dirs));
        
        $cmd = "cd {$projectPath} && tar czf {$snapshotFile} {$dirList}";
        exec($cmd, $output, $exitCode);
        
        if ($exitCode !== 0) {
            Log::warning('Code snapshot creation had warnings', [
                'output' => implode('\n', $output),
            ]);
        }
    }
    
    /**
     * Create backup metadata file
     */
    private function createBackupMetadata(string $backupId, string $backupDir, string $version): void
    {
        $metadata = [
            'backup_id' => $backupId,
            'version' => $version,
            'environment' => env('ENVIRONMENT_ID', gethostname()),
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays(30)->toIso8601String(),
            'php_version' => phpversion(),
            'db_name' => config('database.connections.mysql.database'),
        ];
        
        $metadataFile = "{$backupDir}/{$backupId}_meta.json";
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Restore from a backup
     */
    public function restoreBackup(string $backupId): void
    {
        $backupDir = storage_path('backups');
        $dbFile = "{$backupDir}/{$backupId}_db.sql.gz";
        
        if (!file_exists($dbFile)) {
            throw new Exception("Backup file not found: {$backupId}");
        }
        
        try {
            // Decompress
            exec("gunzip {$dbFile}", $output, $exitCode);
            if ($exitCode !== 0) {
                throw new Exception('Failed to decompress backup');
            }
            
            // Restore database
            $dbName = config('database.connections.mysql.database');
            $dbHost = config('database.connections.mysql.host');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            
            $sqlFile = "{$backupDir}/{$backupId}_db.sql";
            $cmd = "mysql " .
                   "--user={$dbUser} " .
                   "--password='{$dbPass}' " .
                   "--host={$dbHost} " .
                   "{$dbName} < {$sqlFile}";
            
            exec($cmd, $output, $exitCode);
            if ($exitCode !== 0) {
                throw new Exception('Database restore failed: ' . implode('\n', $output));
            }
            
            Log::info("Backup restored successfully: {$backupId}");
            
        } catch (Exception $e) {
            Log::error("Restore failed: {$e->getMessage()}");
            throw $e;
        }
    }
    
    /**
     * Clean old backups (older than 30 days)
     */
    public function cleanOldBackups(): int
    {
        $backupDir = storage_path('backups');
        $deleted = 0;
        
        if (!is_dir($backupDir)) {
            return 0;
        }
        
        foreach (glob("{$backupDir}/*_meta.json") as $metaFile) {
            $metadata = json_decode(file_get_contents($metaFile), true);
            $expiresAt = Carbon::parse($metadata['expires_at']);
            
            if ($expiresAt->isPast()) {
                // Delete all files for this backup
                $backupId = $metadata['backup_id'];
                foreach (glob("{$backupDir}/{$backupId}*") as $file) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
```

---

## 4. Database Migrations Required

### Create update_history Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('update_history', function (Blueprint $table) {
            $table->id();
            $table->string('environment_id');          // Laptop identifier
            $table->string('from_version');
            $table->string('to_version');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'rolled_back']);
            $table->string('backup_id')->nullable();
            $table->longText('log_output')->nullable();
            $table->longText('error_message')->nullable();
            $table->string('executed_by')->nullable(); // User or 'system'
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
            
            $table->index(['environment_id', 'created_at']);
            $table->index(['status']);
        });
    }
    
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('update_history');
    }
};
```

### Enhance system_releases Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('system_releases', function (Blueprint $table) {
            $table->timestamp('installed_at')->nullable()->after('requires_db_update');
            $table->string('environment_id')->nullable()->after('installed_at');
            $table->string('backup_id')->nullable()->after('environment_id');
        });
    }
    
    public function down(): void
    {
        Schema::connection('central')->table('system_releases', function (Blueprint $table) {
            $table->dropColumn(['installed_at', 'environment_id', 'backup_id']);
        });
    }
};
```

---

## 5. Vue Component: UpdateNotificationBanner

### Structure and Implementation

```vue
<template>
  <div v-if="show" :class="['update-banner', updateLevel]">
    <div class="banner-left">
      <div class="banner-icon">
        <component :is="getIconComponent" />
      </div>
      
      <div class="banner-content">
        <h3 class="banner-title">{{ title }}</h3>
        <p class="banner-description">{{ description }}</p>
        
        <ul v-if="features.length" class="banner-features">
          <li v-for="(feature, idx) in features.slice(0, 3)" :key="idx">
            {{ feature }}
          </li>
          <li v-if="features.length > 3" class="text-muted">
            +{{ features.length - 3 }} more
          </li>
        </ul>
      </div>
    </div>
    
    <div class="banner-actions">
      <button @click="handleInstall" :disabled="loading" class="btn btn-primary">
        <span v-if="loading">{{ loadingText }}</span>
        <span v-else>{{ primaryAction }}</span>
      </button>
      
      <button @click="handleSchedule" class="btn btn-secondary">
        Schedule
      </button>
      
      <button @click="handleDismiss" class="btn-text">
        {{ secondaryAction }}
      </button>
    </div>
    
    <button @click="close" class="btn-close">×</button>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  release: {
    type: Object,
    required: true,
  },
  updateLevel: {
    type: String,
    default: 'info', // 'critical', 'warning', 'info', 'low'
  },
  features: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['dismiss', 'install', 'schedule', 'close'])

const show = ref(true)
const loading = ref(false)
const loadingText = ref('Installing...')

const title = computed(() => {
  const levelTitles = {
    critical: '🔴 CRITICAL: Security Update',
    warning: '⚠️ Important Update Available',
    info: '✨ New Features Available',
    low: '📦 Update Available',
  }
  return levelTitles[props.updateLevel] || levelTitles.info
})

const description = computed(() => {
  const levelDesc = {
    critical: 'This security update is required immediately.',
    warning: 'Database changes required. Schedule during off-hours.',
    info: `New update ${props.release?.version} is available.`,
    low: 'A new patch is available.',
  }
  return levelDesc[props.updateLevel] || levelDesc.info
})

const primaryAction = computed(() => {
  return props.updateLevel === 'critical' ? 'Install Now' : 'Install Update'
})

const secondaryAction = computed(() => {
  return props.updateLevel === 'critical' ? 'Remind Me Later' : 'Dismiss'
})

const getIconComponent = computed(() => {
  const icons = {
    critical: 'IconAlert',
    warning: 'IconWarning',
    info: 'IconInfo',
    low: 'IconPackage',
  }
  return icons[props.updateLevel] || icons.info
})

const handleInstall = async () => {
  loading.value = true
  emit('install')
  
  // Trigger update via API
  try {
    const response = await fetch('/api/updates/apply', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        version: props.release.version,
      }),
    })
    
    if (response.ok) {
      const result = await response.json()
      if (result.success) {
        show.value = false
        // Show success notification
      }
    }
  } finally {
    loading.value = false
  }
}

const handleSchedule = () => {
  emit('schedule')
  // Open schedule dialog
}

const handleDismiss = () => {
  emit('dismiss')
  // Maybe store in localStorage to not show for 24hrs
  localStorage.setItem('dismissed_update_' + props.release.version, Date.now())
}

const close = () => {
  show.value = false
  emit('close')
}
</script>

<style scoped lang="scss">
.update-banner {
  display: flex;
  gap: 1.5rem;
  padding: 1rem;
  border-radius: 0.5rem;
  border-left: 5px solid;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  background: white;
  align-items: flex-start;
  
  &.critical {
    background-color: #fef2f2;
    border-color: #dc2626;
    
    .banner-title { color: #991b1b; }
  }
  
  &.warning {
    background-color: #fffbeb;
    border-color: #f59e0b;
    
    .banner-title { color: #92400e; }
  }
  
  &.info {
    background-color: #eff6ff;
    border-color: #3b82f6;
    
    .banner-title { color: #1e40af; }
  }
  
  &.low {
    background-color: #f9fafb;
    border-color: #9ca3af;
    
    .banner-title { color: #374151; }
  }
}

.banner-left {
  flex: 1;
  display: flex;
  gap: 1rem;
}

.banner-icon {
  flex-shrink: 0;
  font-size: 1.5rem;
}

.banner-content {
  flex: 1;
}

.banner-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
}

.banner-description {
  margin: 0.25rem 0 0.5rem;
  font-size: 0.875rem;
  color: #666;
}

.banner-features {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.875rem;
  
  li {
    padding: 0.25rem 0;
    color: #666;
    
    &:before {
      content: '• ';
      margin-right: 0.5rem;
    }
  }
}

.banner-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  
  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.btn-primary {
  background: #3b82f6;
  color: white;
  
  &:hover:not(:disabled) {
    background: #2563eb;
  }
}

.btn-secondary {
  background: #e5e7eb;
  color: #374151;
  
  &:hover:not(:disabled) {
    background: #d1d5db;
  }
}

.btn-text {
  background: none;
  border: none;
  color: #3b82f6;
  cursor: pointer;
  padding: 0;
  
  &:hover {
    text-decoration: underline;
  }
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #9ca3af;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  
  &:hover {
    color: #374151;
  }
}
</style>
```

---

## 6. API Route for Update Application

### Pseudocode

```php
<?php

// routes/api.php

use App\Http\Controllers\UpdateController;

Route::middleware(['auth:api'])->group(function () {
    // Check for updates
    Route::get('/updates/check', [UpdateController::class, 'check']);
    
    // Get update details
    Route::get('/updates/{version}', [UpdateController::class, 'show']);
    
    // Apply update
    Route::post('/updates/apply', [UpdateController::class, 'apply']);
    
    // Get update history
    Route::get('/updates/history', [UpdateController::class, 'history']);
    
    // Schedule update
    Route::post('/updates/{version}/schedule', [UpdateController::class, 'schedule']);
    
    // Cancel scheduled update
    Route::delete('/updates/{version}/schedule', [UpdateController::class, 'cancelSchedule']);
});
```

### Controller Implementation

```php
<?php

namespace App\Http\Controllers;

use App\Services\DistributedUpdateChecker;
use App\Services\DistributedUpdateExecutor;
use App\Models\SystemRelease;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    private DistributedUpdateChecker $checker;
    private DistributedUpdateExecutor $executor;
    
    public function __construct(DistributedUpdateChecker $checker, DistributedUpdateExecutor $executor)
    {
        $this->checker = $checker;
        $this->executor = $executor;
    }
    
    /**
     * Check for available updates
     */
    public function check(): JsonResponse
    {
        try {
            $updateInfo = $this->checker->check();
            
            return response()->json([
                'success' => true,
                'data' => $updateInfo,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Apply an update
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'version' => 'required|string',
        ]);
        
        try {
            $result = $this->executor->execute($request->version);
            
            return response()->json([
                'success' => $result['success'],
                'version' => $result['version'],
                'duration' => $result['duration'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get update history
     */
    public function history(): JsonResponse
    {
        $history = \App\Models\UpdateHistory::latest()
            ->limit(20)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
```

---

## 7. Artisan Command for Scheduled Updates

### Pseudocode

```php
<?php

namespace App\Console\Commands;

use App\Services\DistributedUpdateExecutor;
use App\Models\ScheduledUpdate;
use Illuminate\Console\Command;

class ExecuteScheduledUpdates extends Command
{
    protected $signature = 'updates:execute-scheduled';
    protected $description = 'Execute scheduled updates that are ready';
    
    private DistributedUpdateExecutor $executor;
    
    public function __construct(DistributedUpdateExecutor $executor)
    {
        parent::__construct();
        $this->executor = $executor;
    }
    
    public function handle(): int
    {
        // Find updates scheduled for now
        $scheduled = ScheduledUpdate::where('scheduled_for', '<=', now())
            ->where('status', 'pending')
            ->get();
        
        foreach ($scheduled as $update) {
            $this->info("Executing scheduled update: {$update->version}");
            
            try {
                $result = $this->executor->execute($update->version);
                
                $update->update([
                    'status' => 'completed',
                    'executed_at' => now(),
                ]);
                
                $this->info("✓ Update completed: {$update->version}");
                
            } catch (Exception $e) {
                $update->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
                
                $this->error("✗ Update failed: {$e->getMessage()}");
            }
        }
        
        return Command::SUCCESS;
    }
}
```

---

## 8. Testing Examples

### Unit Test: DistributedUpdateChecker

```php
<?php

namespace Tests\Unit;

use App\Services\DistributedUpdateChecker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DistributedUpdateCheckerTest extends TestCase
{
    /**
     * Test detecting newer version from GitHub
     */
    public function test_detects_newer_version(): void
    {
        Http::fake([
            'api.github.com/repos/Joenstalker/new_dcms/releases/latest' => Http::response([
                'tag_name' => 'v1.2.0',
                'body' => 'New features available',
                'published_at' => now()->toIso8601String(),
            ]),
        ]);
        
        $checker = app(DistributedUpdateChecker::class);
        $result = $checker->check();
        
        $this->assertTrue($result['hasUpdate']);
        $this->assertEquals('v1.2.0', $result['latest']);
    }
    
    /**
     * Test fallback to cache when GitHub is down
     */
    public function test_fallback_to_cache_on_network_error(): void
    {
        Http::fake(['*' => Http::response(null, 500)]);
        
        // Prime the cache
        \Illuminate\Support\Facades\Cache::put('github_latest_release', [
            'tag_name' => 'v1.2.0',
        ], 3600);
        
        $checker = app(DistributedUpdateChecker::class);
        $result = $checker->check();
        
        $this->assertTrue($result['fromCache']);
    }
}
```

---

## Conclusion

This implementation guide provides the foundation for a robust, distributed update mechanism. Key principles:

1. **Decentralized:** Each environment is independent
2. **Reliable:** Multiple fallback layers (cache, backup, rollback)
3. **Safe:** Atomic operations, pre-flight checks, automatic rollback
4. **Simple:** GitHub as single source of truth, code-only updates

Estimated implementation time: **7 weeks** across 5 phases.

