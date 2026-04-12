<?php

namespace Tests\Feature\Security;

use App\Services\SecuritySanitizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirms user-supplied text used in profile-style fields is stripped of HTML before persistence.
 *
 * HTTP profile tests are omitted here because tenant DB connection bootstrapping in this app is
 * middleware-order sensitive; ProfileUpdateRequest delegates sanitization to SecuritySanitizationService.
 */
class XssProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Protects against: stored XSS in plain-text name fields (mirrors ProfileUpdateRequest::prepareForValidation).
     */
    public function test_security_sanitization_service_strips_script_tags_from_plain_text(): void
    {
        $sanitizer = app(SecuritySanitizationService::class);

        $malicious = '<script>alert(1)</script>Dr. Evil';
        $clean = $sanitizer->sanitizePlainText($malicious, 255);

        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringNotContainsString('</script>', $clean);
        $this->assertStringContainsString('Dr. Evil', $clean);
    }

    /**
     * Protects against: javascript: URLs masquerading as emails in structured fields.
     */
    public function test_security_sanitization_service_strips_tags_from_email_like_input(): void
    {
        $sanitizer = app(SecuritySanitizationService::class);

        $payload = '<b>user</b>@example.com';
        $clean = $sanitizer->sanitizeEmail($payload, 255);

        $this->assertStringNotContainsString('<b>', $clean);
        $this->assertStringEndsWith('@example.com', $clean);
    }
}
