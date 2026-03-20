<?php

namespace App\Models;

use App\Services\DataEncryptionService;
use App\Models\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patient extends Model
{
    use HasFactory, HasTenantScope;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'ic_number',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'medical_history',
        'allergies',
        'notes',
        'emergency_contact',
        'emergency_phone',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'last_visit_time' => 'datetime',
        'balance' => 'decimal:2',
    ];

    /**
     * Fields that should be encrypted
     */
    protected array $encryptedFields = [
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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set tenant_id
        static::creating(function ($patient) {
            if (empty($patient->tenant_id)) {
                try {
                    $tenant = tenancy()->tenant();
                    if ($tenant) {
                        $patient->tenant_id = $tenant->getTenantKey();
                    }
                } catch (\Exception $e) {
                    // Tenancy not initialized, skip
                }
            }
        });

        // Encrypt sensitive fields before saving
        static::saving(function ($patient) {
            $encryptionService = app(DataEncryptionService::class);

            foreach ($patient->encryptedFields as $field) {
                if (isset($patient->attributes[$field]) && !empty($patient->attributes[$field])) {
                    // Only encrypt if not already encrypted
                    $value = $patient->attributes[$field];
                    if (!$encryptionService->isEncrypted($value)) {
                        $patient->attributes[$field] = $encryptionService->encrypt($value);
                    }
                }
            }
        });

        // Decrypt fields after retrieval
        static::retrieved(function ($patient) {
            $encryptionService = app(DataEncryptionService::class);

            foreach ($patient->encryptedFields as $field) {
                if (isset($patient->attributes[$field]) && !empty($patient->attributes[$field])) {
                    try {
                        $patient->attributes[$field] = $encryptionService->decrypt($patient->attributes[$field]);
                    }
                    catch (\Exception $e) {
                    // If decryption fails, value might not be encrypted
                    }
                }
            }
        });
    }

    /**
     * Get the patient's full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the patient's IC number (masked for display)
     */
    public function getMaskedIcNumberAttribute(): string
    {
        if (empty($this->ic_number)) {
            return '';
        }

        $encryptionService = app(DataEncryptionService::class);
        return $encryptionService->maskIcNumber($this->ic_number);
    }

    /**
     * Get the patient's phone (masked for display)
     */
    public function getMaskedPhoneAttribute(): string
    {
        if (empty($this->phone)) {
            return '';
        }

        $encryptionService = app(DataEncryptionService::class);
        return $encryptionService->maskPhone($this->phone);
    }

    /**
     * Get the appointments for the patient.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the treatments for the patient.
     */
    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    /**
     * Get the invoices for the patient.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeTenant($query)
    {
        try {
            $tenant = tenancy()->tenant();
            if ($tenant) {
                return $query->where('tenant_id', $tenant->id);
            }
        } catch (\Exception $e) {
            // Tenancy not initialized, skip
        }

        return $query;
    }
}
