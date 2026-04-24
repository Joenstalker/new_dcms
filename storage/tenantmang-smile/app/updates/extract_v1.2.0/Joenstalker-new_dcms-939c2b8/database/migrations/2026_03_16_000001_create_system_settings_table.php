<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general'); // session, branding, security, billing, etc.
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default system settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }

    /**
     * Seed default system settings.
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            // Session Settings
            [
                'key' => 'session_lifetime',
                'value' => '120',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Session lifetime in minutes',
            ],
            [
                'key' => 'session_expire_on_close',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'session',
                'description' => 'Expire session when browser is closed',
            ],
            [
                'key' => 'session_encrypt',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'session',
                'description' => 'Encrypt session data',
            ],
            [
                'key' => 'remember_me_duration',
                'value' => '20160',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Remember me duration in minutes (14 days)',
            ],
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Maximum failed login attempts before lockout',
            ],
            [
                'key' => 'lockout_duration',
                'value' => '15',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Lockout duration in minutes after max attempts',
            ],
            [
                'key' => 'password_reset_expiry',
                'value' => '60',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Password reset token expiry in minutes',
            ],

            // Branding Settings
            [
                'key' => 'platform_name',
                'value' => 'DCMS',
                'type' => 'string',
                'group' => 'branding',
                'description' => 'Platform name displayed on login pages',
            ],
            [
                'key' => 'platform_logo',
                'value' => null,
                'type' => 'string',
                'group' => 'branding',
                'description' => 'Platform logo path',
            ],
            [
                'key' => 'primary_color',
                'value' => '#0ea5e9',
                'type' => 'string',
                'group' => 'branding',
                'description' => 'Primary accent color',
            ],
            [
                'key' => 'footer_text',
                'value' => '© 2026 DCMS. All rights reserved.',
                'type' => 'string',
                'group' => 'branding',
                'description' => 'Footer text displayed on pages',
            ],

            // Security Settings (Coming Soon flags)
            [
                'key' => 'two_factor_auth_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Enable two-factor authentication (Coming Soon)',
            ],
            [
                'key' => 'ip_whitelist_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Enable IP whitelist restriction (Coming Soon)',
            ],
            [
                'key' => 'ip_whitelist',
                'value' => '[]',
                'type' => 'json',
                'group' => 'security',
                'description' => 'List of allowed IP addresses (Coming Soon)',
            ],
            [
                'key' => 'audit_logging_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Enable audit logging',
            ],
            [
                'key' => 'data_encryption_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Enable data encryption for sensitive fields',
            ],
            [
                'key' => 'concurrent_sessions_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Allow multiple concurrent sessions (Coming Soon)',
            ],

            // Maintenance Settings
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Enable system maintenance mode',
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'System is under maintenance. Please try again later.',
                'type' => 'string',
                'group' => 'maintenance',
                'description' => 'Message displayed during maintenance mode',
            ],
        ];

        foreach ($settings as $setting) {
            \DB::table('system_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'type' => $setting['type'],
                'group' => $setting['group'],
                'description' => $setting['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
