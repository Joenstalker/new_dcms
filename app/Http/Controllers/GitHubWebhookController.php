<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AppVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    /**
     * Handle incoming GitHub webhooks for real-time system updates.
     */
    public function handle(Request $request)
    {
        $signature = $request->header('X-Hub-Signature-256');
        $secret = config('services.github.webhook_secret');

        if (!$signature || !$secret) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 1. Validate Signature for Security
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::debug("GitHub Webhook Signature Mismatch!", [
                'received' => $signature,
                'expected' => $expectedSignature,
                'secret_configured' => (bool)$secret,
                'payload_length' => strlen($payload)
            ]);

            AuditLog::record(
                'github_webhook_failed',
                'GitHub Webhook signature verification failed.',
                'System',
                null,
            ['ip' => $request->ip(), 'signature' => $signature]
            );
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 2. Identify Event Type
        $event = $request->header('X-GitHub-Event');
        $data = $request->all();

        if ($event === 'release' && ($data['action'] ?? '') === 'published') {
            $version = $data['release']['tag_name'] ?? 'unknown';

            Log::info("GitHub Webhook: Published release detected - {$version}");

            try {
                // 3. Clear Update Cache
                AppVersionService::clearCache();

                // 4. Trigger System Update Check
                Artisan::call('system:check-updates');

                AuditLog::record(
                    'github_webhook_success',
                    "GitHub Webhook triggered system update check for release: {$version}",
                    'System',
                    null,
                ['version' => $version, 'action' => $data['action']]
                );

                return response()->json(['message' => 'Update check triggered successfully']);
            }
            catch (\Exception $e) {
                Log::error("GitHub Webhook processing failed: " . $e->getMessage());
                return response()->json(['message' => 'Trigger failed'], 500);
            }
        }

        return response()->json(['message' => 'Event ignored']);
    }
}
