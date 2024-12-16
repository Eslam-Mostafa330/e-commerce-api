<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\StoreImageTrait;
use Illuminate\Support\Facades\Storage;

class ProductsController extends Controller
{
    use StoreImageTrait;

    /**
     * Retrieves all products with pagination.
     * - Selects relevant fields (id, name, image, quantity, current price, and offer price) from the products table.
     * - If no products are found, returns a message indicating no products are available.
     * - Transforms the product data to exclude null values for unselected fields, especially for the image.
     * - If there are more products than can fit on one page, includes pagination details in the response.
     * - Returns a success response with the paginated products or a message if no products are found.
     */
    public function index()
    {
        $products = Product::select(
            'id', 'name', 'image',
            'quantity', 'current_price', 'offer_price',
            )
            ->paginate(10);

        if ($products->isEmpty()) {
            return ApiResponse::sendResponse(200, 'No Products found.', null);
        }

        // Transform the collection to exclude null values for unselected fields
        $response = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image ? url('storage/' . $product->image) : null,
                'quantity' => $product->quantity,
                'current_price' => $product->current_price,
                'offer_price' => $product->offer_price,
            ];
        });

        // Include pagination details
        if ($products->total() > $products->perPage()) {
            $response = [
                'records' => $response,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total_records' => $products->total(),
                    'total_pages' => $products->lastPage(),
                    'links' => [
                        'first' => $products->url(1),
                        'last' => $products->url($products->lastPage()),
                        'prev' => $products->previousPageUrl(),
                        'next' => $products->nextPageUrl(),
                    ],
                ],
            ];
        }

        return ApiResponse::sendResponse(200, 'Products retrieved successfully', $response);
    }

    /**
     * Creates a new product.
     * - Validates the request data using the StoreProductRequest.
     * - Handles the image upload by calling a helper method (`storeImage`).
     * - Creates a new product in the database with the validated data.
     * - Returns a success response with the newly created product.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        // Handle image upload
        $data['image'] = $this->storeImage($request, 'image', 'products');
        $product = Product::create($data);
        return ApiResponse::sendResponse(201, 'Product Created Successfully', $product);
    }

    /**
     * Updates an existing product.
     * - Finds the product by its ID and validates the request data using the UpdateProductRequest.
     * - Handles the image update by calling a helper method (`storeImage`) while preserving the old image path.
     * - Updates the product with the new data.
     * - Returns a success response with the updated product information.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();

        // Handle image update
        $data['image'] = $this->storeImage($request, 'image', 'products', oldImagePath: $product->image);
        $product->update($data);
        return ApiResponse::sendResponse(200, 'Product updated successfully', new ProductResource($product));
    }

    /**
     * Retrieves a specific product by its ID.
     * - Selects detailed fields from the product and its associated category.
     * - If the product is found, it returns the product data along with the category details.
     * - Returns a success response with the product information.
     */
    public function show(string $id)
    {
        $product = Product::select(
            'id', 'category_id',
            'name', 'image',
            'quantity', 'description', 'sku',
            'seo_title', 'seo_description',
            'current_price', 'offer_price', 'offer_start_date', 'offer_end_date'
            )
            ->with('category:id,name')
            ->findOrFail($id);
        return ApiResponse::sendResponse(201, 'Product retrieved successfully', new ProductResource($product));
    }

    /**
     * Deletes a specific product by its ID.
     * - Finds the product by its ID.
     * - If the product has an associated image, it deletes the image from the storage.
     * - Deletes the product from the database.
     * - Returns a success response confirming the product was deleted.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        Storage::disk('public')->exists($product->image) && Storage::disk('public')->delete($product->image);
        $product->delete();
        return ApiResponse::sendResponse(200, 'Product deleted successfully', null);
    }
}
