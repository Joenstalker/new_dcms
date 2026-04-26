<?php

namespace Database\Seeders;

use App\Models\SystemRelease;
use App\Services\GitHubService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SystemReleaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(GitHubService $gitHubService): void
    {
        try {
            $latestTag = $gitHubService->getLatestReleaseTag();
            $version = $latestTag ?? 'v1.0.0';

            SystemRelease::updateOrCreate(
                ['version' => $version],
                [
                    'release_notes' => $latestTag ? 'Initial release from GitHub' : 'Fallback version',
                    'released_at' => now(),
                    'is_mandatory' => true,
                    'requires_db_update' => false,
                ]
            );

            $this->command->info("System release version set to: {$version}");
        } catch (\Exception $e) {
            Log::error('SystemReleaseSeeder: Failed to seed system release.', [
                'message' => $e->getMessage(),
            ]);
            $this->command->error('Failed to seed system release. Check logs.');
        }
    }
}
