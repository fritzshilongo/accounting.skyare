<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;
    protected $primaryKey = 'client_id';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'vat_number',
        'tax_number',
        'registration_number',
        'status'
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (trim((string) $term) === '') {
            return $query;
        }

        $term = '%' . trim($term) . '%';

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
              ->orWhere('email', 'like', $term)
              ->orWhere('phone', 'like', $term);
        });
    }

    public function scopeType($query, ?string $type)
    {
        if (! in_array($type, ['individual', 'company'], true)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public function scopeStatus($query, ?string $status)
    {
        if (! in_array($status, ['active', 'inactive', 'suspended'], true)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function getOutstandingAttribute()
    {
        return $this->invoices()
            ->where('status', '!=', 'paid')
            ->sum('amount');
    }
}
