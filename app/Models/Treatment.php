<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;

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
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
