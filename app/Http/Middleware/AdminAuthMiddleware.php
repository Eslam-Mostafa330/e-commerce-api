<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (!Auth::guard('admins')->check()) {
        //     return ApiResponse::sendResponse(401, 'Unauthorized');
        // }
        if (Auth::check() && $request->user() instanceof \App\Models\Admin) {
            return $next($request);
        }
        // \Log::info('Authenticated user', ['user' => $request->user()]);

        // if (Auth::guard('admins')->check() && $request->user() instanceof \App\Models\Admin) {
        //     return $next($request);
        // }

        return ApiResponse::sendResponse(403, 'Unauthorized access', null);
        // return $next($request);
    }
}
