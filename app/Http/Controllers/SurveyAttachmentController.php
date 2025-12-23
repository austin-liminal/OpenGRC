<?php

namespace App\Http\Controllers;

use App\Models\SurveyAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyAttachmentController extends Controller
{
    /**
     * Download a survey attachment (authenticated users only).
     */
    public function download(Request $request, SurveyAttachment $attachment): StreamedResponse
    {
        // Verify the file exists
        $disk = $attachment->getStorageDisk();

        if (! Storage::disk($disk)->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk($disk)->download(
            $attachment->file_path,
            $attachment->file_name
        );
    }
}
