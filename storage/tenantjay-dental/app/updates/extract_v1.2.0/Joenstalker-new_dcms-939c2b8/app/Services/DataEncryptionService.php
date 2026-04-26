<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Data Encryption Service
 * 
 * Provides field-level encryption for sensitive data in the tenant database.
 * Uses Laravel's built-in encryption with AES-256-CBC.
 * 
 * Sensitive fields that should be encrypted:
 * - Patient: IC number, phone, address, medical history, notes
 * - User: phone, address, personal details
 */
class DataEncryptionService
{
    /**
     * Fields that should be encrypted for patients
     */
    public const PATIENT_ENCRYPTED_FIELDS = [
        'ic_number',
        'phone',
        'address',
        'medical_history',
        'allergies',
        'notes',
        'emergency_contact',
        'emergency_phone',
    ];

    /**
     * Fields that should be encrypted for users
     */
    public const USER_ENCRYPTED_FIELDS = [
        'phone',
        'address',
        'personal_details',
    ];

    /**
     * Encrypt sensitive patient data before saving
     * 
     * @param array $data
     * @return array
     */
    public function encryptPatientData(array $data): array
    {
        foreach (self::PATIENT_ENCRYPTED_FIELDS as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Decrypt sensitive patient data after retrieval
     * 
     * @param array $data
     * @return array
     */
    public function decryptPatientData(array $data): array
    {
        foreach (self::PATIENT_ENCRYPTED_FIELDS as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Encrypt sensitive user data before saving
     * 
     * @param array $data
     * @return array
     */
    public function encryptUserData(array $data): array
    {
        foreach (self::USER_ENCRYPTED_FIELDS as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Decrypt sensitive user data after retrieval
     * 
     * @param array $data
     * @return array
     */
    public function decryptUserData(array $data): array
    {
        foreach (self::USER_ENCRYPTED_FIELDS as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Encrypt a single value
     * 
     * @param mixed $value
     * @return string
     */
    public function encrypt(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Crypt::encryptString($value);
        }
        catch (\Exception $e) {
            Log::error('Encryption failed', [
                'error' => $e->getMessage(),
                'value_length' => strlen($value)
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt a single value
     * 
     * @param string $encryptedValue
     * @return string
     */
    public function decrypt(string $encryptedValue): string
    {
        if (empty($encryptedValue)) {
            return '';
        }

        try {
            return Crypt::decryptString($encryptedValue);
        }
        catch (\Exception $e) {
            Log::error('Decryption failed', [
                'error' => $e->getMessage(),
                'value_length' => strlen($encryptedValue)
            ]);
            // Return original value if decryption fails (might not be encrypted)
            return $encryptedValue;
        }
    }

    /**
     * Check if a value is already encrypted
     *
     * @param string $value
     * @return bool
     */
    public function isEncrypted(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Try to decrypt - if it works, it was encrypted
        try {
            Crypt::decryptString($value);
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate a hash for data integrity verification
     * 
     * @param mixed $data
     * @return string
     */
    public function generateHash(mixed $data): string
    {
        return hash('sha256', json_encode($data));
    }

    /**
     * Verify data integrity using hash
     * 
     * @param mixed $data
     * @param string $expectedHash
     * @return bool
     */
    public function verifyHash(mixed $data, string $expectedHash): bool
    {
        return $this->generateHash($data) === $expectedHash;
    }

    /**
     * Mask sensitive data for display (e.g., IC number: XXXXXXXX1234)
     * 
     * @param string $value
     * @param int $visibleChars
     * @return string
     */
    public function maskSensitiveData(string $value, int $visibleChars = 4): string
    {
        if (empty($value)) {
            return '';
        }

        $length = strlen($value);

        if ($length <= $visibleChars) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - $visibleChars) . substr($value, -$visibleChars);
    }

    /**
     * Mask IC number specifically
     * 
     * @param string $icNumber
     * @return string
     */
    public function maskIcNumber(string $icNumber): string
    {
        return $this->maskSensitiveData($icNumber, 4);
    }

    /**
     * Mask phone number
     * 
     * @param string $phone
     * @return string
     */
    public function maskPhone(string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Keep last 4 digits visible
        return $this->maskSensitiveData($phone, 4);
    }
}
