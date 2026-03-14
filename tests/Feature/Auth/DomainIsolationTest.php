<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Stancl\Tenancy\Database\Models\Domain;

class DomainIsolationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup central domain
        config(['tenancy.central_domains' => ['localhost', 'dcms.test']]);
    }

    public function test_super_admin_can_login_on_central_domain()
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@dcms.test',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $response = $this->from('http://dcms.test/login')
            ->post('http://dcms.test/login', [
                'email' => 'admin@dcms.test',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('admin.dashboard', [], false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_tenant_user_cannot_login_on_central_domain()
    {
        $user = User::create([
            'name' => 'Tenant Owner',
            'email' => 'owner@clinic.test',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->from('http://dcms.test/login')
            ->post('http://dcms.test/login', [
                'email' => 'owner@clinic.test',
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_cannot_login_on_tenant_domain()
    {
        // Setup a tenant
        $id = 'test' . uniqid();
        $tenant = Tenant::create(['id' => $id]);
        $tenant->domains()->create(['domain' => $id . '.dcms.test']);

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@dcms.test',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        // Note: In tests, we need to handle tenant initialization
        tenancy()->initialize($tenant);
        $tenant->run(function () {
            $this->artisan('migrate', ['--path' => 'database/migrations/tenant', '--realpath' => true]);
        });

        $response = $this->from("http://{$id}.dcms.test/login")
            ->post("http://{$id}.dcms.test/login", [
                'email' => 'admin@dcms.test',
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tenant_user_can_login_on_tenant_domain()
    {
        // Setup a tenant
        $id = 'test' . uniqid();
        $tenant = Tenant::create(['id' => $id]);
        $tenant->domains()->create(['domain' => $id . '.dcms.test']);

        // Initialize tenancy to create user in tenant DB
        tenancy()->initialize($tenant);
        $tenant->run(function () {
            $this->artisan('migrate', ['--path' => 'database/migrations/tenant', '--realpath' => true]);
        });
        
        $user = User::create([
            'name' => 'Tenant Owner',
            'email' => 'owner@clinic.test',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->from("http://{$id}.dcms.test/login")
            ->post("http://{$id}.dcms.test/login", [
                'email' => 'owner@clinic.test',
                'password' => 'password',
            ]);

        // Redirection for tenant dashboard
        $response->assertRedirect(route('tenant.dashboard', [], false));
        $this->assertAuthenticatedAs($user);
    }
}
