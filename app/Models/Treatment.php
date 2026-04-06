<?php

namespace App\Models;

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
        'diagnosis',
        'procedure',
        'notes',
        'cost',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class , 'dentist_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
