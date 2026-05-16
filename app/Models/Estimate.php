<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\EstimateItem;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estimate extends Model
{
    use HasFactory;
    protected $primaryKey = 'estimate_id';

    public $timestamps = true;

    protected $fillable = [
        'company_id',
        'client_id',
        'customer_id',
        'product_id',
        'client_name',
        'quantity',
        'unit_price',
        'amount',
        'tax_amount',
        'total',
        'estimate_date',
        'expiry_date',
        'status'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class, 'estimate_id');
    }
}
