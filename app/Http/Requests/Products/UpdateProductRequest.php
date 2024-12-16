<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'quantity' => ['nullable', 'numeric', 'min:1'],
            'sku' => ['nullable', 'string', 'max:100'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'current_price' => ['nullable', 'numeric'],
            'offer_price' => ['nullable', 'numeric'],
            'offer_start_date' => ['nullable', 'date_format:d/m/Y', 'after_or_equal:now'],
            'offer_end_date' => ['nullable', 'date_format:d/m/Y', 'after:offer_start_date'],
        ];
    }
}
