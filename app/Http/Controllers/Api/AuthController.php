<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registers a new user.
     * - Validates the incoming registration data.
     * - Creates a new user with the provided name, email, and hashed password.
     * - Transfers cart items from the guest user (if any) to the newly registered user.
     * - Generates an API token for the user and includes it in the response along with the user's name and email.
     * - Returns a success response with the token, name, and email.
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Transfer guest cart items to this new user
        $this->transferGuestCart($request, $user->id);

        $data['token'] = $user->createToken('UserToken')->plainTextToken;
        // Using name and email to make these values available to use after the user register
        $data['name'] = $user->name;
        $data['email'] = $user->email;

        // we could return the "data" or "null" or "empty array"
        // based on the other developers preference (mobile dev or front dev..etc)
        return ApiResponse::sendResponse(201, 'User Created Successfully', $data);
    }

    /**
     * Logs an existing user in.
     * - Validates the incoming login credentials.
     * - Attempts to authenticate the user based on the provided email and password.
     * - If authentication is successful, transfers cart items from the guest user (if any) to the authenticated user.
     * - Generates an API token for the user and includes it in the response along with the user's name and email.
     * - Returns a success response with the token, name, and email.
     * - If authentication fails, returns an error response indicating invalid credentials.
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            $user = Auth::user();

            // Transfer guest cart items to this user
            $this->transferGuestCart($request, $user->id);

            $data['token'] = $user->createToken('UserToken')->plainTextToken;
            $data['name'] = $user->name;
            $data['email'] = $user->email;

            return ApiResponse::sendResponse(200, 'User Logged In Successfully', $data);
        }

        return ApiResponse::sendResponse(401, 'User Credentials does not exist', null);
    }

    /**
     * Logs out the authenticated user.
     * - Deletes the current access token of the authenticated user, effectively logging them out.
     * - Returns a success response indicating the user has logged out successfully.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::sendResponse(200, 'Logged Out Successfully', null);
    }

    /**
     * Transfers cart items from a guest user to an authenticated user.
     * - Retrieves the guest token from the request cookies.
     * - If a guest token exists, fetches all cart items associated with it.
     * - Iterates over each cart item:
     *   - If the same product exists in the authenticated user's cart, it updates the quantity and deletes the guest cart item.
     *   - If the product doesn't exist in the authenticated user's cart, it assigns the item to the user and removes the guest token.
     */
    private function transferGuestCart(Request $request, $userId)
    {
        $guestToken = $request->cookie('guest_token');

        if (!$guestToken) {
            return;
        }

        $guestCartItems = Cart::where('guest_token', $guestToken)->get();

        foreach ($guestCartItems as $item) {
            $existingItem = Cart::where('user_id', $userId)
                ->where('product_id', $item->product_id)
                ->first();

            $existingItem
                ? $existingItem->update(['quantity' => $existingItem->quantity + $item->quantity]) && $item->delete()
                : $item->update(['user_id' => $userId, 'guest_token' => null]);
        }
    }

}
