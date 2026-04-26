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
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
        'operation_history',
        'allergies',
        'notes',
        'emergency_phone',
        'photo_path',
        'patient_type',
        'tags',
        'first_visit_at',
        'last_recall_at',
        'last_visit_time',
        'balance',
        'initial_balance',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'last_visit_time' => 'datetime',
        'first_visit_at' => 'date',
        'last_recall_at' => 'date',
        'tags' => 'array',
        'balance' => 'decimal:2',
        'initial_balance' => 'decimal:2',
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['full_name', 'photo_url'];

    /**
     * Get the patient's photo URL.
     */
    public function getPhotoUrlAttribute(): string
    {
        if (empty($this->photo_path)) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->getFullNameAttribute()) . '&color=7F9CF5&background=EBF4FF';
        }

        if (str_starts_with($this->photo_path, 'data:image')) {
            return $this->photo_path;
        }

        return tenant_asset($this->photo_path);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate 10-digit ID and set tenant_id
        static::creating(function ($patient) {
            if (empty($patient->id)) {
                $datePrefix = date('ymd');
                do {
                    $t = microtime(true);
                    $microPart = sprintf("%06d", ($t - floor($t)) * 1000000);
                    $fragment = substr($microPart, 0, 4);
                    $id = (int)($datePrefix . $fragment);
                } while (self::where('id', $id)->exists());

                $patient->id = $id;
            }

            if (empty($patient->tenant_id)) {
                try {
                    $tenant = tenancy()->tenant();
                    if ($tenant) {
                        $patient->tenant_id = $tenant->getTenantKey();
                    }
                }
                catch (\Exception $e) {
                // Tenancy not initialized, skip
                }
            }
        });

        // Update storage usage when photo changes
        static::updated(function ($patient) {
            if ($patient->wasChanged('photo_path')) {
                $oldPath = $patient->getOriginal('photo_path');
                $newPath = $patient->photo_path;

                $oldSize = (str_starts_with($oldPath ?? '', 'data:image')) ? strlen($oldPath) : 0;
                $newSize = (str_starts_with($newPath ?? '', 'data:image')) ? strlen($newPath) : 0;

                $diff = $newSize - $oldSize;
                if ($diff !== 0) {
                    $tenant = $patient->tenant ?? Tenant::find($patient->tenant_id);
                    if ($tenant) {
                        $tenant->increment('storage_used_bytes', $diff);
                    }
                }
            }
        });

        // Add storage usage on creation
        static::created(function ($patient) {
            if ($patient->photo_path && str_starts_with($patient->photo_path, 'data:image')) {
                $size = strlen($patient->photo_path);
                $tenant = $patient->tenant ?? Tenant::find($patient->tenant_id);
                if ($tenant) {
                    $tenant->increment('storage_used_bytes', $size);
                }
            }
        });

        // Subtract storage usage on deletion
        static::deleted(function ($patient) {
            if ($patient->photo_path && str_starts_with($patient->photo_path, 'data:image')) {
                $size = strlen($patient->photo_path);
                $tenant = $patient->tenant ?? Tenant::find($patient->tenant_id);
                if ($tenant) {
                    $tenant->decrement('storage_used_bytes', $size);
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

            // Ensure attributes are synced to original to prevent accidental re-encryption on save
            $patient->syncOriginal();
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
     * Get the tenant for the patient.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class , 'tenant_id');
    }

    /**
     * Recalculate and save the patient's balance.
     * Balance = Initial Balance + Total Treatment Due - Total Paid in Progress Notes.
     */
    public function recalculateBalance(): void
    {
        $totalCharges = (float) $this->treatments()->sum('total_amount_due');
        $totalPaid = (float) $this->treatments()->sum('amount_paid');

        $this->balance = round($this->initial_balance + $totalCharges - $totalPaid, 2);
        $this->saveQuietly();
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
        }
        catch (\Exception $e) {
        // Tenancy not initialized, skip
        }

        return $query;
    }
}
