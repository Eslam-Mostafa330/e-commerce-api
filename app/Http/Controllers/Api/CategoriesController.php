<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = Category::select('id', 'name')->paginate(10);

        if ($categories->isEmpty()) {
            return ApiResponse::sendResponse(200, 'No Categories found.', null);
        }

        $response = CategoryResource::collection($categories);

        if ($categories->total() > $categories->perPage()) {
            $response = [
                'records' => CategoryResource::collection($categories),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total_records' => $categories->total(),
                    'total_pages' => $categories->lastPage(),
                    'links' => [
                        'first' => $categories->url(1),
                        'last' => $categories->url($categories->lastPage()),
                        'prev' => $categories->previousPageUrl(),
                        'next' => $categories->nextPageUrl(),
                    ],
                ],
            ];
        }

        return ApiResponse::sendResponse(200, 'Categories retrieved successfully', $response);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        $category = Category::create($validated);
        return ApiResponse::sendResponse(201, 'Category Created Successfully', $category);
    }

    /**
     * Display specified category.
     */
    public function show(string $id)
    {
        $category = Category::select('id', 'name', 'created_at')->findOrFail($id);
        return ApiResponse::sendResponse(201, 'Category retrieved successfully', $category);
    }

    /**
     * Update specified category.
     */
    public function update(StoreCategoryRequest $request, string $id)
    {
        $category = Category::findOrFail($id);
        $data = $request->validated();
        $category->update($data);
        return ApiResponse::sendResponse(200, 'Category updated successfully', new CategoryResource($category));
    }

    /**
     * Remove specified category.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id)->delete();
        return ApiResponse::sendResponse(200, 'Category Deleted successfully', $category);
    }
}
