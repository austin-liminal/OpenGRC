<?php

namespace App\Http\Middleware;

use App\Models\VendorUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireVendorPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('vendor')->user();

        // If user is logged in and doesn't have a password, redirect to set password page
        if ($user instanceof VendorUser && ! $user->hasPassword()) {
            // Allow access to set-password page and logout
            if ($request->routeIs('filament.vendor.auth.set-password') || $request->routeIs('filament.vendor.auth.logout')) {
                return $next($request);
            }

            return redirect()->route('filament.vendor.auth.set-password');
        }

        return $next($request);
    }
}
