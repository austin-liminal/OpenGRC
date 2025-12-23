<?php

use App\Livewire\PasswordResetPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
});

// override default login route to point to Filament login
Route::get('login', function () {
    return redirect()->route('filament.app.auth.login');
})->name('login');

Route::middleware(['auth'])->group(function () {

    Route::get('/app/reset-password', PasswordResetPage::class)->name('password-reset-page');

    Route::get('/app/priv-storage/{filepath}', function ($filepath) {
        return Storage::disk('private')->download($filepath);
    })->where('filepath', '.*')->name('priv-storage');

    // Media proxy route for serving private S3/cloud storage files
    Route::get('/media/{path}', [\App\Http\Controllers\MediaProxyController::class, 'show'])
        ->where('path', '.*')
        ->name('media.show');

    // Survey attachment download route
    Route::get('/survey-attachment/{attachment}/download', [\App\Http\Controllers\SurveyAttachmentController::class, 'download'])
        ->name('survey-attachment.download');

});

// Add Socialite routes
Route::get('auth/{provider}/redirect', '\App\Http\Controllers\Auth\AuthController@redirectToProvider')->name('socialite.redirect');
Route::get('auth/{provider}/callback', '\App\Http\Controllers\Auth\AuthController@handleProviderCallback')->name('socialite.callback');

// Legacy public survey routes - redirect to new magic link URL
// (Surveys are now handled through the authenticated Vendor Panel with magic links)
Route::get('survey/{token}', function ($token) {
    // Find the survey by access token
    $survey = \App\Models\Survey::where('access_token', $token)->first();

    if (! $survey) {
        abort(404, 'Survey not found');
    }

    // Redirect to the new magic link URL
    return redirect($survey->getPublicUrl());
})->name('survey.show');

// Vendor Portal Magic Link Routes
Route::get('/portal/auth/magic-login/{vendorUser}', [\App\Http\Controllers\Vendor\VendorAuthController::class, 'magicLogin'])
    ->name('vendor.magic-login')
    ->middleware('signed');

// Survey-specific magic link - logs in vendor and redirects to survey
Route::get('/portal/survey/{survey}/respond', [\App\Http\Controllers\Vendor\VendorAuthController::class, 'surveyMagicLink'])
    ->name('vendor.survey.magic-link')
    ->middleware('signed');

// Vendor Survey Access Page (login/register flow - no auth required)
Route::get('/portal/survey-access', \App\Filament\Vendor\Pages\Auth\SurveyAccess::class)
    ->name('filament.vendor.pages.survey-access');

// Vendor Document Download Route (requires vendor auth)
Route::middleware(['auth:vendor'])->group(function () {
    Route::get('/portal/document/{vendorDocument}/download', [\App\Http\Controllers\Vendor\VendorDocumentController::class, 'download'])
        ->name('vendor.document.download');
});
