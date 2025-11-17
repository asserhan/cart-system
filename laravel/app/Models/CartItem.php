<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

}