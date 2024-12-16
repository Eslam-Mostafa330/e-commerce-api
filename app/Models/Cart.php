<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'guest_token',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
