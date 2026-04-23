<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DistributedUpdateChecker;
use App\Services\DistributedUpdateExecutor;

class RunDistributedUpdate extends Command
{
    protected $signature = 'system:distributed-update {--apply : Apply update if available} {--tag= : Specific tag to apply}';

    protected $description = 'Check for distributed updates and optionally apply the latest or specified tag';

    public function handle(DistributedUpdateChecker $checker, DistributedUpdateExecutor $executor)
    {
        $this->info('Checking for updates...');
        $res = $checker->check();

        $this->line('Current: ' . ($res['current'] ?? 'unknown'));
        $this->line('Latest: ' . ($res['latest'] ?? 'unknown') . ($res['fromCache'] ? ' (cached)' : ''));

        if (! empty($res['error'])) {
            $this->warn($res['error']);
        }

        $apply = $this->option('apply');
        $tag = $this->option('tag');

        if ($tag) {
            $this->info("Applying specified tag: {$tag}");
            $result = $executor->executeUpdate($tag, $res['release'] ?? []);
            $this->info('Result: ' . json_encode($result));
            return 0;
        }

        if ($apply && ($res['hasUpdate'] ?? false)) {
            $this->info('Update available — applying...');
            $result = $executor->executeUpdate($res['latest'], $res['release'] ?? []);
            $this->info('Result: ' . json_encode($result));
            return 0;
        }

        if (! ($res['hasUpdate'] ?? false)) {
            $this->info('No update available.');
            return 0;
        }

        $this->info('Run with --apply to apply the latest update.');
        return 0;
    }
}
