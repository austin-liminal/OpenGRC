<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorAuthController extends Controller
{
    public function magicLogin(Request $request, VendorUser $vendorUser)
    {
        // Validate signed URL
        if (! $request->hasValidSignature()) {
            abort(401, 'This link has expired or is invalid.');
        }

        // Check if vendor user is not deleted
        if ($vendorUser->trashed()) {
            abort(403, 'This account has been deactivated.');
        }

        // Log the user in
        Auth::guard('vendor')->login($vendorUser);

        // Update last login
        $vendorUser->update(['last_login_at' => now()]);

        // Regenerate session
        session()->regenerate();

        // If user has no password, redirect to set password page
        if (! $vendorUser->hasPassword()) {
            return redirect()->route('filament.vendor.auth.set-password');
        }

        // Otherwise, redirect to dashboard
        return redirect()->intended(Filament::getPanel('vendor')->getUrl());
    }

    /**
     * Magic link to access a specific survey.
     * Redirects to the survey access page where users can login, register, or set password.
     */
    public function surveyMagicLink(Request $request, Survey $survey)
    {
        // Validate signed URL
        if (! $request->hasValidSignature()) {
            abort(401, 'This link has expired or is invalid.');
        }

        // Check survey exists and has a vendor
        if (! $survey->vendor_id) {
            abort(404, 'Survey not found.');
        }

        // Get the respondent email from the survey
        $email = $survey->respondent_email;

        // Check if user is already logged in as vendor
        if (Auth::guard('vendor')->check()) {
            $vendorUser = Auth::guard('vendor')->user();

            // Verify user belongs to this vendor
            if ($vendorUser->vendor_id === $survey->vendor_id) {
                // Already logged in to the right vendor, go directly to survey
                return redirect()->route('filament.vendor.resources.surveys.respond', ['record' => $survey->id]);
            }

            // Wrong vendor - log them out and continue to auth page
            Auth::guard('vendor')->logout();
        }

        // Redirect to survey access page with survey ID and email
        return redirect()->route('filament.vendor.pages.survey-access', [
            'survey' => $survey->id,
            'email' => $email,
        ]);
    }
}
