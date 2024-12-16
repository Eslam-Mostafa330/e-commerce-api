<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\AdminRegisterRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Registers a new admin.
     * - Validates the incoming registration data.
     * - Creates a new admin with the provided name, email, and hashed password.
     * - Generates an API token for the admin and includes it in the response along with the admin's name and email.
     * - Returns a success response with the token, name, and email.
     */
    public function register(AdminRegisterRequest $request)
    {
        $validated = $request->validated();

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $data['token'] = $admin->createToken('AdminToken')->plainTextToken;
        // Using name and email to make these values available to use after the user register
        $data['name'] = $admin->name;
        $data['email'] = $admin->email;

        // we could return the data or null or empty array
        // based on the other developers preference (mobile dev or front dev..etc)
        return ApiResponse::sendResponse(201, 'Admin Created Successfully', $data);
    }


    /**
     * Logs an existing admin in.
     * - Validates the incoming login credentials.
     * - Attempts to authenticate the admin based on the provided email and password.
     * - Generates an API token for the admin and includes it in the response along with the user's name and email.
     * - Returns a success response with the token, name, and email.
     * - If authentication fails, returns an error response indicating invalid credentials.
     */
    public function login(AdminLoginRequest $request)
    {
        $validated = $request->validated();

        if (Auth::guard('admins')->attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            $admin = Auth::guard('admins')->user();

            $data['token'] = $admin->createToken('adminToken')->plainTextToken;
            $data['name'] = $admin->name;
            $data['email'] = $admin->email;

            return ApiResponse::sendResponse(200, 'Admin Logged In Successfully', $data);
        }

        return ApiResponse::sendResponse(401, 'Admin Credentials do not exist', null);
    }

    /**
     * Logs out the authenticated admin.
     * - Deletes the current access token of the authenticated admin, effectively logging them out.
     * - Returns a success response indicating the admin has logged out successfully.
     */
    public function logout(Request $request)
    {
        $admin = $request->user();
    
        if (!$admin instanceof Admin) {
            return ApiResponse::sendResponse(401, 'Unauthorized', null);
        }

        $admin->currentAccessToken()->delete();
        return ApiResponse::sendResponse(200, 'Logged Out Successfully', null);
    }

}
