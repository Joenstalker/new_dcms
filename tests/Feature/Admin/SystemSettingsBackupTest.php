<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemSettingsBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_admin' => true,
        ]);
    }

    #[Test]
    public function admin_can_view_system_settings_page_with_backup_tab()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.system-settings.index'));

        $response->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/SystemSettings/Index')
            ->has('backupData', fn (AssertableInertia $page) => $page
                ->where('google_drive_connected', false)
                ->where('last_backup', null)
                ->etc()
            )
        );
    }

    #[Test]
    public function manual_backup_requires_google_drive_connection()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.system-settings.backup.run'));

        $response->assertStatus(400);
        $response->assertJson([ 'success' => false ]);
    }

    #[Test]
    public function service_account_configuration_skips_google_oauth_connect()
    {
        Config::set('services.google_drive.service_account_json', json_encode([
            'type' => 'service_account',
            'project_id' => 'test-project',
            'private_key_id' => 'fake-key-id',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nFAKE\n-----END PRIVATE KEY-----\n",
            'client_email' => 'service-account@test-project.iam.gserviceaccount.com',
            'client_id' => '1234567890',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/service-account@test-project.iam.gserviceaccount.com',
        ]));
        Config::set('services.google_drive.folder_id', 'folder-id');

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.drive.connect'));

        $response->assertRedirect('/admin/system-settings');
        $response->assertSessionHas('success', 'Google Drive is configured through service account credentials; OAuth sign-in is not required.');
    }
}
