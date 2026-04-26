<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Users (staff/dentists) assigned to this branch.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Patients registered at this branch.
     */
    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * Appointments scheduled at this branch.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Set this branch as primary and un-set others.
     */
    public function makePrimary()
    {
        static::where('id', '!=', $this->id)->update(['is_primary' => false]);
        $this->update(['is_primary' => true]);
    }
}
