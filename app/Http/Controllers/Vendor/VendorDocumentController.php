<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorDocumentController extends Controller
{
    public function download(VendorDocument $vendorDocument): StreamedResponse
    {
        $vendorUser = Auth::guard('vendor')->user();

        // Verify the document belongs to the vendor user's vendor
        if ($vendorDocument->vendor_id !== $vendorUser->vendor_id) {
            abort(403, 'You do not have access to this document.');
        }

        // Check if file exists
        if (! Storage::disk(config('filesystems.default'))->exists($vendorDocument->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk(config('filesystems.default'))
            ->download($vendorDocument->file_path, $vendorDocument->file_name);
    }
}
