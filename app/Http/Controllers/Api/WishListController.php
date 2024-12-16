<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishListController extends Controller
{
    /**
     * Retrieves all products in the authenticated user's wishlist.
     * - Fetches the wishlist items for the authenticated user, including the associated product details (name).
     * - Paginate the wishlist items to show 10 items per page.
     * - If the wishlist is empty, returns a message indicating no products are found.
     * - If there are more items than can fit on one page, includes pagination details in the response.
     * - Returns a success response with the wishlist items or a message if the wishlist is empty.
     */
    public function index()
    {
        $wishlist = Wishlist::select('id', 'user_id', 'product_id')
            ->where('user_id', auth()->id())
            ->with('product:id,name')
            ->paginate(10);

        if ($wishlist->isEmpty()) {
            return ApiResponse::sendResponse(200, 'No Products found in your wishlist.', null);
        }

        $response = WishlistResource::collection($wishlist);

        // Include pagination details if applicable
        if ($wishlist->total() > $wishlist->perPage()) {
            $response = [
                'records' => WishlistResource::collection($wishlist),
                'pagination' => [
                    'current_page' => $wishlist->currentPage(),
                    'per_page' => $wishlist->perPage(),
                    'total_records' => $wishlist->total(),
                    'total_pages' => $wishlist->lastPage(),
                    'links' => [
                        'first' => $wishlist->url(1),
                        'last' => $wishlist->url($wishlist->lastPage()),
                        'prev' => $wishlist->previousPageUrl(),
                        'next' => $wishlist->nextPageUrl(),
                    ],
                ],
            ];
        }
        return ApiResponse::sendResponse(200, 'Wishlist retrieved successfully', $response);
    }

    /**
     * Adds or removes a product from the authenticated user's wishlist.
     * - Validates the provided product ID to ensure it exists in the products table.
     * - If the product is already in the wishlist, it will be removed.
     * - If the product is not in the wishlist, it will be added to the wishlist.
     * - Returns a success response indicating the action taken (added or removed).
     */
    public function toggleWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::select('id', 'user_id', 'product_id')->firstOrNew([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
        ]);

        if ($wishlist->exists) {
            $wishlist->delete();
            return ApiResponse::sendResponse(200, 'Product removed from wishlist', null);
        }

        $wishlist->save();
        return ApiResponse::sendResponse(201, 'Product added to wishlist', $request->product_id);
    }

    /**
     * Removes a product from the authenticated user's wishlist.
     * - Validates the provided product ID to ensure it exists in the wishlist.
     * - If the product is found in the wishlist, it is deleted.
     * - Returns a success response if the product was removed, or an error if the product was not found in the wishlist.
     */
    public function removeFromWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::select('id', 'user_id', 'product_id')
            ->where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();
        
        return $wishlist 
        ? ($wishlist->delete() ? ApiResponse::sendResponse(200, 'Product removed from wishlist.', null) : null)
        : ApiResponse::sendResponse(404, 'Wishlist item not found.', null);
    }
}
