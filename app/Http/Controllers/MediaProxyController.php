<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaProxyController extends Controller
{
    /**
     * Serve private media files through Laravel proxy
     * This allows browser-based editors to display private S3 images
     */
    public function show(Request $request, string $path): StreamedResponse
    {
        // Get the storage disk (defaults to configured disk)
        $disk = Storage::disk(config('filesystems.default'));

        // Decode the path (in case it was URL encoded)
        $filePath = urldecode($path);

        // Security: Prevent directory traversal attacks
        if (str_contains($filePath, '..') || str_starts_with($filePath, '/')) {
            abort(403, 'Invalid file path');
        }

        // Check if file exists
        if (! $disk->exists($filePath)) {
            abort(404, 'File not found');
        }

        // Get file metadata
        try {
            $mimeType = $disk->mimeType($filePath);
            $size = $disk->size($filePath);
        } catch (\Exception $e) {
            abort(500, 'Unable to retrieve file metadata');
        }

        // Stream the file from S3 (efficient for large files)
        return new StreamedResponse(function () use ($disk, $filePath) {
            $stream = $disk->readStream($filePath);
            if ($stream === false) {
                abort(500, 'Unable to read file');
            }
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
            'Content-Disposition' => 'inline', // Display in browser, not download
        ]);
    }
}
