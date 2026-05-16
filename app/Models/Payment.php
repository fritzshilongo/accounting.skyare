<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'method'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $invoice = Invoice::find($payment->invoice_id);
            if (! $invoice) {
                throw new \Exception('Invoice not found for payment.');
            }
            $balance = $invoice->balance;
            if ((float) $payment->amount > (float) $balance) {
                throw new \Exception('Payment amount exceeds invoice balance.');
            }
        });

        static::created(function (Payment $payment) {
            $payment->invoice?->refreshPaymentStatus();
        });

        static::deleted(function (Payment $payment) {
            $payment->invoice?->refreshPaymentStatus();
        });
    }
}

