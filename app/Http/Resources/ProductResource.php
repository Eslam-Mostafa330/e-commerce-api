<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image ? url('storage/' . $this->image) : null,
            'quantity' => $this->quantity,
            'sku' => $this->sku,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'description' => $this->description,
            'current_price' => $this->current_price,
            'offer_price' => $this->offer_price,
            'offer_start_date' => $this->offer_start_date,
            'offer_end_date' => $this->offer_end_date,
            'effective_price' => $this->effective_price,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name
            ] : null, 
        ];
    }
}
