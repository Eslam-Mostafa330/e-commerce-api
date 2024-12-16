<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index(Request $request)
    {
        // Get user context (auth user or guest user)
        $context = $this->getUserContext($request);
    
        // Retrieve cart items for the current user or guest
        $cartItems = Cart::where($context['userId'] ? 'user_id' : 'guest_token', $context['userId'] ?? $context['guestToken'])
            ->with('product:id,name,current_price')
            ->paginate(10);
    
        if ($cartItems->isEmpty()) {
            return ApiResponse::sendResponse(200, 'No items to view.', null);
        }
    
        // Default response format for cart items
        $response = CartItemResource::collection($cartItems);
    
        // Pagination details if the total items exceed per-page limit
        if ($cartItems->total() > $cartItems->perPage()) {
            $response = [
                'records' => CartItemResource::collection($cartItems),
                'pagination' => [
                    'current_page' => $cartItems->currentPage(),
                    'per_page' => $cartItems->perPage(),
                    'total_records' => $cartItems->total(),
                    'total_pages' => $cartItems->lastPage(),
                    'links' => [
                        'first' => $cartItems->url(1),
                        'last' => $cartItems->url($cartItems->lastPage()),
                        'prev' => $cartItems->previousPageUrl(),
                        'next' => $cartItems->nextPageUrl(),
                    ],
                ],
            ];
        }
    
        return ApiResponse::sendResponse(200, 'Cart items retrieved successfully', $response);
    }
    

    /**
     * Retrieves the user context for cart operations.
     * - If the user is authenticated, returns their user ID and null guest token.
     * - If not authenticated, checks for an existing guest token or generates a new one.
     * - Attaches the new guest token as a cookie with a 7-day expiration.
     */
    protected function getUserContext(Request $request)
    {
        if (auth()->check()) {
            return ['userId' => auth()->id(), 'guestToken' => null];
        }

        // Check if a guest_token exists in the request cookies
        $guestToken = $request->cookie('guest_token');

        // If guest_token doesn't exist, generate a new one and attach it to the response
        if (!$guestToken) {
            $guestToken = (string) Str::uuid();
            response()->cookie('guest_token', $guestToken, 10080);
        }

        return ['userId' => null, 'guestToken' => $guestToken];
    }

    /**
     * Adds a product to the user's cart.
     * - Validates product ID and quantity.
     * - Uses the user context (authenticated or guest) to identify the cart.
     * - If the product already exists in the cart, increments its quantity.
     * - Otherwise, creates a new cart entry.
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $context = $this->getUserContext($request);

        // Retrieve the cart item for the current user or guest based on the user context and product ID
        $cartItem = Cart::select('id', 'user_id', 'guest_token' ,'product_id')
            ->where($context['userId'] 
            ? ['user_id' => $context['userId']] 
            : ['guest_token' => $context['guestToken']]
        )->where('product_id', $request->product_id)->first();

        // Update the cart item's quantity if it exists; otherwise, create a new cart entry
        $cartItem
        ? $cartItem->update(['quantity' => $cartItem->quantity + $request->quantity])
        : Cart::create([
            'user_id' => $context['userId'],
            'guest_token' => $context['guestToken'],
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
        ]);
        
        return ApiResponse::sendResponse(201, 'Item added to cart successfully.', [
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
        ]);
    }

    /**
     * Removes a product from the user's cart.
     * - Validates product ID.
     * - Uses the user context to locate the cart item.
     * - Deletes the item if it exists; otherwise, returns a 404 response.
     */
    public function removeFromCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $context = $this->getUserContext($request);

        $cartItem = Cart::select('id', 'user_id', 'guest_token' ,'product_id')
            ->where($context['userId'] 
            ? ['user_id' => $context['userId']] 
            : ['guest_token' => $context['guestToken']]
        )->where('product_id', $request->product_id)->first();

        if ($cartItem) {
            $cartItem->delete();
            return ApiResponse::sendResponse(200, 'Product removed from cart successfully.');
        }

        return ApiResponse::sendResponse(404, 'Item not found in cart.');
    }

    /**
     * Calculates the total price of all items in the user's cart.
     * - Retrieves cart items based on the user context (authenticated or guest).
     * - Multiplies each item's current price by its quantity to calculate the total.
     * - Returns the total price in the response.
     */
    public function calculateTotalPrice(Request $request)
    {
        $context = $this->getUserContext($request);

        // Retrieve all cart items for the current user or guest based on the user context
        $cartItems = Cart::select('id', 'user_id', 'guest_token', 'product_id')
            ->where(function ($query) use ($context) {
            if ($context['userId']) {
                $query->where('user_id', $context['userId']);
            } else {
                $query->where('guest_token', $context['guestToken']);
            }
        })->get();

        $totalPrice = $cartItems->sum(function ($item) {
            return $item->product->current_price * $item->quantity;
        });

        return ApiResponse::sendResponse(200, 'Total price calculated successfully.', [
            'total_price' => $totalPrice,
        ]);
    }
}
