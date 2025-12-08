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

// Public Survey Response Routes (no authentication required)
Route::prefix('survey')->name('survey.')->group(function () {
    Route::get('{token}', [\App\Http\Controllers\SurveyResponseController::class, 'show'])->name('show');
    Route::post('{token}/save', [\App\Http\Controllers\SurveyResponseController::class, 'save'])->name('save');
    Route::post('{token}/submit', [\App\Http\Controllers\SurveyResponseController::class, 'submit'])->name('submit');
    Route::post('{token}/upload', [\App\Http\Controllers\SurveyResponseController::class, 'uploadFile'])->name('upload');
    Route::match(['post', 'delete'], '{token}/file/{attachmentId}', [\App\Http\Controllers\SurveyResponseController::class, 'deleteFile'])->name('delete-file');
});
