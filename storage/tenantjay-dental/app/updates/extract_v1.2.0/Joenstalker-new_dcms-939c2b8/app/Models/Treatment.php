<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::saved(function ($treatment) {
            $treatment->patient->recalculateBalance();
        });

        static::deleted(function ($treatment) {
            $treatment->patient->recalculateBalance();
        });
    }

    protected $fillable = [
        'patient_id',
        'dentist_id',
        'appointment_id',
        'service_id',
        'diagnosis',
        'procedure',
        'notes',
        'cost',
        'payment_account',
        'discount',
        'total_amount_due',
        'amount_paid',
        'is_last_visit',
        'linked_treatment_id',
        'commission_deductions',
        'commission_percentage',
        'commission_net',
        'commission_use_percentage',
        'schedule_next_visit',
        'next_visit_at',
        'next_visit_procedure',
        'next_visit_dentist_id',
        'next_visit_remarks',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'commission_deductions' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_net' => 'decimal:2',
        'is_last_visit' => 'boolean',
        'commission_use_percentage' => 'boolean',
        'schedule_next_visit' => 'boolean',
        'next_visit_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Treatment, $this>
     */
    public function linkedTreatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class, 'linked_treatment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function nextVisitDentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'next_visit_dentist_id');
    }
}
