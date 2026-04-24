<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminSupportAttachmentTest extends TestCase
{
    use DatabaseMigrations;

    #[Test]
    public function admin_receives_tenant_image_attachment_without_breaking(): void
    {
        config(['app.url' => 'http://localhost:8080']);
        $this->withServerVariables([
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
        ]);

        $tenantId = 'tenant-admin-support-' . uniqid();

        $tenant = Tenant::withoutEvents(function () use ($tenantId) {
            return Tenant::create([
                'id' => $tenantId,
                'name' => 'Tenant For Admin Support Attachment',
                'email' => 'owner@tenant-admin-support.test',
            ]);
        });

        $admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@dcms.test',
            'is_admin' => true,
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => (string) $tenant->id,
            'user_id' => 1,
            'subject' => 'Broken tenant image check',
            'description' => 'Checking tenant image visibility in admin support.',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
        ]);

        $tenantMessage = $ticket->messages()->create([
            'sender_id' => 1,
            'sender_type' => 'tenant',
            'content' => 'Tenant sent an image attachment.',
        ]);

        $path = 'support/attachments/admin-receives-tenant-image.png';
        $tenantStorageRoot = storage_path(config('tenancy.filesystem.suffix_base', 'tenant').(string) $tenant->id.'/app/public');
        $fullFilePath = $tenantStorageRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (! is_dir(dirname($fullFilePath))) {
            mkdir(dirname($fullFilePath), 0777, true);
        }

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Zx1kAAAAASUVORK5CYII=');
        file_put_contents($fullFilePath, $pngBytes);

        $attachment = $tenantMessage->attachments()->create([
            'file_path' => $path,
            'file_name' => 'admin-receives-tenant-image.png',
            'file_type' => 'image/png',
            'file_size' => strlen((string) $pngBytes),
        ]);

        $showResponse = $this
            ->actingAs($admin)
            ->getJson('http://localhost:8080/admin/support/' . $ticket->id);

        $showResponse
            ->assertOk()
            ->assertJsonPath(
                'data.ticket.messages.0.attachments.0.url',
                '/admin/support/' . $ticket->id . '/attachments/' . $attachment->id
            );

        $attachmentResponse = $this
            ->actingAs($admin)
            ->get('http://localhost:8080/admin/support/' . $ticket->id . '/attachments/' . $attachment->id);

        $attachmentResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }
}
