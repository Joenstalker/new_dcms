<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantSupportAutoReplyTest extends TestCase
{
    use DatabaseMigrations;

    private const AUTO_REPLY = "Thank you for reaching out with your concern.\nWe value your feedback and will review it promptly.";

    protected Tenant $tenant;

    protected User $owner;

    protected string $tenantDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteTenantSqliteFiles();

        $tenantId = 'tenant-support-' . uniqid();
        $this->tenantDomain = $tenantId . '.dcms.test';

        config(['app.url' => 'http://'.$this->tenantDomain]);
        $this->withServerVariables([
            'HTTP_HOST' => $this->tenantDomain,
            'SERVER_NAME' => $this->tenantDomain,
        ]);

        $this->tenant = Tenant::withoutEvents(function () use ($tenantId) {
            return Tenant::create([
                'id' => $tenantId,
                'name' => 'Support Tenant Clinic',
                'email' => 'owner@support-tenant.test',
            ]);
        });

        $this->tenant->domains()->create([
            'domain' => $this->tenantDomain,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Support Test Plan',
            'slug' => 'support-test-plan',
            'price_monthly' => 99,
            'stripe_monthly_price_id' => 'price_support_test_plan',
            'max_users' => 100,
            'max_patients' => 1000,
        ]);
        $plan->features()->detach();

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
            'stripe_id' => 'sub_support_test_001',
        ]);

        $this->provisionTenantSqliteAndMigrate($this->tenant);
        tenancy()->initialize($this->tenant);

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->owner = User::factory()->create([
            'name' => 'Tenant Owner',
            'email' => 'owner@support-tenant.test',
        ]);
        $this->owner->assignRole('Owner');
    }

    protected function tearDown(): void
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        $this->deleteTenantSqliteFiles();

        parent::tearDown();
    }

    #[Test]
    public function auto_reply_is_created_when_tenant_submits_initial_ticket_message(): void
    {
        $response = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->postJson('http://' . $this->tenantDomain . '/support', [
                'subject' => 'Unable to receive booking',
                'category' => 'technical',
                'priority' => 'high',
                'content' => 'Please check our online booking issue.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $ticket = SupportTicket::query()->latest('id')->firstOrFail();
        $messages = $ticket->messages()->orderBy('id')->get();

        $this->assertCount(2, $messages);
        $this->assertSame('tenant', $messages[0]->sender_type);
        $this->assertSame('Please check our online booking issue.', $messages[0]->content);

        $this->assertSame('admin', $messages[1]->sender_type);
        $this->assertSame(0, (int) $messages[1]->sender_id);
        $this->assertSame(self::AUTO_REPLY, $messages[1]->content);
    }

    #[Test]
    public function auto_reply_is_not_sent_again_for_second_tenant_message(): void
    {
        $storeResponse = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->postJson('http://' . $this->tenantDomain . '/support', [
                'subject' => 'POS integration concern',
                'category' => 'technical',
                'priority' => 'medium',
                'content' => 'Initial issue message.',
            ]);

        $storeResponse->assertCreated();

        $ticket = SupportTicket::query()->latest('id')->firstOrFail();

        $messageResponse = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->postJson('http://' . $this->tenantDomain . '/support/' . $ticket->id . '/messages', [
                'content' => 'Second follow-up message from tenant.',
            ]);

        $messageResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $ticket->refresh();
        $messages = $ticket->messages()->orderBy('id')->get();

        $this->assertCount(3, $messages);
        $this->assertSame('tenant', $messages[2]->sender_type);
        $this->assertSame('Second follow-up message from tenant.', $messages[2]->content);

        $this->assertSame(1, $ticket->messages()
            ->where('sender_type', 'admin')
            ->where('content', self::AUTO_REPLY)
            ->count());
    }

    #[Test]
    public function missing_admin_profile_picture_falls_back_to_default_avatar_in_tenant_support_view(): void
    {
        tenancy()->end();

        $admin = User::factory()->create([
            'name' => 'DCMS Admin',
            'email' => 'admin-support-fallback@dcms.test',
            'is_admin' => true,
            'profile_picture' => 'profile-pictures/missing-admin-avatar.jpeg',
        ]);

        tenancy()->initialize($this->tenant);

        $ticket = SupportTicket::create([
            'tenant_id' => (string) $this->tenant->id,
            'user_id' => $this->owner->id,
            'subject' => 'Avatar fallback test',
            'description' => 'Ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
        ]);

        $ticket->messages()->create([
            'sender_id' => $this->owner->id,
            'sender_type' => 'tenant',
            'content' => 'Initial tenant message',
        ]);

        $ticket->messages()->create([
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'content' => 'Admin response',
        ]);

        $response = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->getJson('http://' . $this->tenantDomain . '/support/' . $ticket->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $messages = $response->json('data.ticket.messages');
        $adminMessage = collect($messages)->firstWhere('sender_type', 'admin');

        $this->assertNotNull($adminMessage);
        $this->assertStringContainsString('ui-avatars.com/api/', (string) ($adminMessage['sender_avatar_url'] ?? ''));
    }

    #[Test]
    public function tenant_support_api_exposes_attachment_url_and_streams_tenant_attachment(): void
    {
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Zx1kAAAAASUVORK5CYII=');

        $ticket = SupportTicket::create([
            'tenant_id' => (string) $this->tenant->id,
            'user_id' => $this->owner->id,
            'subject' => 'Tenant attachment route test',
            'description' => 'Ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
        ]);

        $message = $ticket->messages()->create([
            'sender_id' => $this->owner->id,
            'sender_type' => 'tenant',
            'content' => 'Tenant attachment message',
        ]);

        $path = 'support/attachments/tenant-route-check.png';
        Storage::disk('support')->put($path, $pngBytes);

        $attachment = $message->attachments()->create([
            'file_path' => $path,
            'file_name' => 'tenant-route-check.png',
            'file_type' => 'image/png',
            'file_size' => 20,
        ]);

        $showResponse = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->getJson('http://' . $this->tenantDomain . '/support/' . $ticket->id);

        $showResponse->assertOk();

        $returnedPath = 'data.ticket.messages.0.attachments.0.url';
        $showResponse->assertJsonPath($returnedPath, '/support/' . $ticket->id . '/attachments/' . $attachment->id);

        $attachmentResponse = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->get('http://' . $this->tenantDomain . '/support/' . $ticket->id . '/attachments/' . $attachment->id);

        $attachmentResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    #[Test]
    public function tenant_support_attachment_route_streams_admin_attachment_from_central_storage(): void
    {
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Zx1kAAAAASUVORK5CYII=');

        tenancy()->end();

        $admin = User::factory()->create([
            'name' => 'Central Admin Attachment',
            'email' => 'admin-attachment@dcms.test',
            'is_admin' => true,
        ]);

        tenancy()->initialize($this->tenant);

        $ticket = SupportTicket::create([
            'tenant_id' => (string) $this->tenant->id,
            'user_id' => $this->owner->id,
            'subject' => 'Admin attachment route test',
            'description' => 'Ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
        ]);

        $adminMessage = $ticket->messages()->create([
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'content' => 'Admin attachment message',
        ]);

        $path = 'support/attachments/admin-route-check.png';
        $centralSupportRoot = base_path('storage/app/public/support/attachments');
        if (! is_dir($centralSupportRoot)) {
            mkdir($centralSupportRoot, 0777, true);
        }
        file_put_contents(base_path('storage/app/public/' . $path), $pngBytes);

        $attachment = $adminMessage->attachments()->create([
            'file_path' => $path,
            'file_name' => 'admin-route-check.png',
            'file_type' => 'image/png',
            'file_size' => 19,
        ]);

        $response = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->get('http://' . $this->tenantDomain . '/support/' . $ticket->id . '/attachments/' . $attachment->id);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    #[Test]
    public function tenant_cannot_reply_when_ticket_is_closed(): void
    {
        $ticket = SupportTicket::create([
            'tenant_id' => (string) $this->tenant->id,
            'user_id' => $this->owner->id,
            'subject' => 'Closed ticket guard test',
            'description' => 'Ticket description',
            'status' => 'closed',
            'priority' => 'medium',
            'category' => 'technical',
        ]);

        $ticket->messages()->create([
            'sender_id' => $this->owner->id,
            'sender_type' => 'tenant',
            'content' => 'Initial message before closure.',
        ]);

        $response = $this
            ->actingAs($this->owner)
            ->withSession([
                'tenant_authenticated' => true,
                'tenant_authenticated_tenant_id' => (string) $this->tenant->id,
                'tenant_authenticated_user_id' => (int) $this->owner->id,
            ])
            ->postJson('http://' . $this->tenantDomain . '/support/' . $ticket->id . '/messages', [
                'content' => 'Tenant follow-up on closed ticket.',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This ticket is closed and read-only.');

        $this->assertSame(1, $ticket->messages()->count());
    }
}
