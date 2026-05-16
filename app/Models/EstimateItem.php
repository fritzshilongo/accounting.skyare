<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EstimateItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'estimate_item_id';
    protected $fillable = [
        'estimate_id',
        'product_id',
        'description',
        'price',
        'quantity'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id');
    }
}
