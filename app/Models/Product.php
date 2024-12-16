<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'image',
        'quantity',
        'sku',
        'seo_title',
        'seo_description',
        'description',
        'current_price',
        'offer_price',
        'offer_start_date',
        'offer_end_date',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getEffectivePriceAttribute()
    {
        $currentDate = \Carbon\Carbon::now();
    
        // If offer price exists and current date is within the offer period, use offer price
        if ($this->offer_price && $currentDate->between($this->offer_start_date, $this->offer_end_date)) {
            return $this->offer_price;
        }
    
        // Otherwise, use current price
        return $this->current_price;
    }
    

    protected function setOfferStartDateAttribute($value)
    {
        $this->attributes['offer_start_date'] = $value
            ? \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d')
            : null;
    }

    protected function setOfferEndDateAttribute($value)
    {
        $this->attributes['offer_end_date'] = $value
            ? \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d')
            : null;
    }


    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}
