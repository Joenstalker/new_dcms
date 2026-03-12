<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'guest_first_name',
        'guest_last_name',
        'guest_phone',
        'guest_email',
        'appointment_date',
        'status',
        'service',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }
}
