<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'dentist_id',
        'guest_first_name',
        'guest_last_name',
        'guest_phone',
        'guest_email',
        'guest_address',
        'guest_medical_history',
        'booking_reference',
        'appointment_date',
        'status',
        'type',
        'service',
        'notes',
        'photo_path',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'guest_medical_history' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }
}
