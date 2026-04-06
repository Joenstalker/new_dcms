<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::saved(function ($invoice) {
            $invoice->patient->recalculateBalance();
        });

        static::deleted(function ($invoice) {
            $invoice->patient->recalculateBalance();
        });
    }

    protected $fillable = [
        'patient_id',
        'total_amount',
        'amount_paid',
        'status',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function updateAmountPaid()
    {
        $this->amount_paid = $this->payments()->sum('amount');

        if ($this->amount_paid >= $this->total_amount) {
            $this->status = 'paid';
        }
        elseif ($this->amount_paid > 0) {
            $this->status = 'partially_paid';
        }
        else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}
