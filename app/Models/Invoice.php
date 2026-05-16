<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;
    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'company_id',
        'client_id',
        'client_name',
        'invoice_no',
        'amount',
        'tax_rate',
        'tax_amount',
        'total',
        'issue_date',
        'due_date',
        'status',
        'paid_at'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
    }

    public function getPaidAmountAttribute(): float
    {
        try {
            return (float) $this->payments()->sum('amount');
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    public function getBalanceAttribute(): float
    {
        $balance = (float) $this->total - $this->paid_amount;
        return $balance < 0 ? 0.0 : $balance;
    }

    public function getStatusAttribute($value): string
    {
        $base = strtolower((string) ($value ?? 'unpaid'));
        if ($base === 'partial') {
            $base = 'partial_paid';
        }
        if ($base === 'finalized') {
            $base = 'finalised';
        }

        if (in_array($base, ['draft', 'accepted', 'sent', 'viewed', 'cancelled', 'finalised', 'paid', 'partial_paid', 'overpaid', 'unpaid'], true)) {
            return $base === 'unpaid' ? 'accepted' : $base;
        }

        try {
            $paid = (float) $this->payments()->sum('amount');
        } catch (\Throwable $e) {
            return $base !== '' ? $base : 'draft';
        }
        $total = (float) ($this->total ?? $this->amount ?? 0);

        if ($total <= 0 && $paid <= 0) {
            return $base !== '' ? $base : 'draft';
        }

        if ($paid <= 0) {
            if ($base === 'paid' || $base === 'finalised') {
                return 'paid';
            }

            return $base === 'draft' ? 'draft' : 'accepted';
        }

        if ($paid < max(0.0001, $total)) {
            return 'partial_paid';
        }

        if (abs($paid - $total) <= 0.0001) {
            return 'paid';
        }

        return 'overpaid';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        if ($this->status === 'paid') {
            return false;
        }

        try {
            return now()->startOfDay()->gt(
                \Carbon\Carbon::parse($this->due_date)->endOfDay()
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    public function refreshPaymentStatus(): self
    {
        $paid = (float) $this->payments()->sum('amount');
        $total = (float) ($this->total ?? $this->amount ?? 0);
        $current = strtolower((string) ($this->attributes['status'] ?? 'draft'));

        if ($current === 'partial') {
            $current = 'partial_paid';
        }
        if ($current === 'finalized') {
            $current = 'finalised';
        }

        if ($paid <= 0) {
            $newStatus = in_array($current, ['draft', 'accepted', 'sent', 'viewed', 'cancelled'], true)
                ? $current
                : 'accepted';
            $this->paid_at = null;
        } elseif ($paid < max(0.0001, $total)) {
            $newStatus = 'partial_paid';
            $this->paid_at = null;
        } else {
            $newStatus = in_array($current, ['finalised'], true) ? 'finalised' : 'paid';
            $this->paid_at = now();
        }

        $this->attributes['status'] = $newStatus;
        $this->saveQuietly();

        return $this;
    }
}

