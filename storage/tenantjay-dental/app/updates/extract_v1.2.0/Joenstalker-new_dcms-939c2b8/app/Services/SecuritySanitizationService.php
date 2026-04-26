<?php

namespace App\Services;

class SecuritySanitizationService
{
    /**
     * Sanitize plain text by stripping HTML and control characters.
     */
    public function sanitizePlainText(?string $value, int $maxLength = 255): string
    {
        $value = $this->normalizeInput($value);
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return mb_substr(trim($value), 0, $maxLength);
    }

    /**
     * Sanitize user-entered multiline text while preserving line breaks.
     */
    public function sanitizeMultilineText(?string $value, int $maxLength = 10000): string
    {
        $value = $this->normalizeInput($value);
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = preg_replace('/\r\n?/', "\n", $value) ?? '';

        return mb_substr(trim($value), 0, $maxLength);
    }

    /**
     * Sanitize emails before validation rules are evaluated.
     */
    public function sanitizeEmail(?string $value, int $maxLength = 255): string
    {
        $value = $this->sanitizePlainText($value, $maxLength);

        return strtolower($value);
    }

    private function normalizeInput(?string $value): string
    {
        return trim((string) $value);
    }
}
