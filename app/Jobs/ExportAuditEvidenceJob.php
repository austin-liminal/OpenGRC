<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\FileAttachment;
use App\Http\Controllers\PdfHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportAuditEvidenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $auditId;

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->auditId))->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($auditId)
    {
        $this->auditId = $auditId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Set cache flag to indicate export is running
        \Cache::put("audit_{$this->auditId}_exporting", true, now()->addHours(2));

        \Log::info("ExportAuditEvidenceJob started for audit {$this->auditId}");

        $audit = Audit::with([
            'auditItems',
            'auditItems.dataRequests.responses.attachments',
            'auditItems.auditable',
        ])->findOrFail($this->auditId);

        $exportPath = storage_path("app/exports/audit_{$this->auditId}/");
        if (! Storage::exists("app/exports/audit_{$this->auditId}/") && ! Storage::disk('s3') && ! Storage::disk('digitalocean')) {
            Storage::makeDirectory("app/exports/audit_{$this->auditId}/");
        }

        $disk = setting('storage.driver', 'private');
        $allFiles = [];

        // Get all data requests for this audit (supports both old and new relationships)
        $dataRequests = \App\Models\DataRequest::where('audit_id', $this->auditId)
            ->with(['responses.attachments', 'auditItems.auditable', 'auditItem.auditable'])
            ->get();

        \Log::info("Found {$dataRequests->count()} data requests to export");

        // Directory/key prefix for exports
        $exportDir = "exports/audit_{$this->auditId}/";

        // Create a local temp directory for all files
        $tmpDir = sys_get_temp_dir()."/audit_{$this->auditId}_".uniqid();
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        foreach ($dataRequests as $dataRequest) {
            \Log::info("Processing data request {$dataRequest->id}");
            $dataRequest->loadMissing(['responses.attachments', 'auditItems.auditable']);

            // Collect all attachments for processing
            $attachments = [];
            $pdfAttachments = [];
            $otherAttachments = [];

            foreach ($dataRequest->responses as $response) {
                foreach ($response->attachments as $attachment) {
                    $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                    if (in_array($ext, $imageExts)) {
                        // Image: add base64 for PDF embedding
                        $storage = \Storage::disk($disk);
                        $attachment->base64_image = null;
                        if ($storage->exists($attachment->file_path)) {
                            $imgRaw = $storage->get($attachment->file_path);
                            $mime = $storage->mimeType($attachment->file_path);
                            $attachment->base64_image = 'data:'.$mime.';base64,'.base64_encode($imgRaw);
                        }
                        $attachments[] = $attachment;
                    } elseif ($ext === 'pdf') {
                        // PDF: collect for merging
                        $pdfAttachments[] = $attachment;
                    } else {
                        // Other files: export as original
                        $otherAttachments[] = $attachment;
                    }
                }
            }

            // Generate the main PDF with embedded images
            // Support both single audit item (old) and multiple audit items (new many-to-many)
            $pdfData = [
                'audit' => $audit,
                'dataRequest' => $dataRequest,
            ];

            // Check if using many-to-many relationship
            if ($dataRequest->auditItems && $dataRequest->auditItems->count() > 0) {
                $pdfData['auditItems'] = $dataRequest->auditItems;
                \Log::info("Data request {$dataRequest->id} has {$dataRequest->auditItems->count()} audit items (many-to-many)");
            } elseif ($dataRequest->auditItem) {
                $pdfData['auditItem'] = $dataRequest->auditItem;
                \Log::info("Data request {$dataRequest->id} has single audit item (legacy)");
            } else {
                \Log::info("Skipping data request {$dataRequest->id} - no audit items");
                continue;
            }

            $pdf = Pdf::loadView('pdf.audit-item', $pdfData);

            // Determine filename prefix
            $filenamePrefix = $dataRequest->code ?
                'data_request_'.str_replace([' ', '/', '\\', '|', ':', '*', '?', '"', '<', '>', '.'], '_', $dataRequest->code) :
                "data_request_{$dataRequest->id}";

            $mainPdfPath = $tmpDir.'/'.$filenamePrefix.'.pdf';
            $pdf->save($mainPdfPath);

            // If there are PDF attachments, merge them with the main PDF
            if (! empty($pdfAttachments)) {
                $tempMainPath = $tmpDir.'/'.$filenamePrefix.'_temp.pdf';
                rename($mainPdfPath, $tempMainPath);
                PdfHelper::mergePdfs($tempMainPath, $pdfAttachments, $mainPdfPath, $disk);
                unlink($tempMainPath);
            }

            $allFiles[] = $mainPdfPath;
            \Log::info("Generated PDF for data request {$dataRequest->id}: {$mainPdfPath}");

            // Export other attachments with prefixed names
            foreach ($otherAttachments as $attachment) {
                $storage = \Storage::disk($disk);
                if ($storage->exists($attachment->file_path)) {
                    $originalExt = pathinfo($attachment->file_name, PATHINFO_EXTENSION);
                    $newFilename = $filenamePrefix.'_'.$attachment->file_name;
                    $localPath = $tmpDir.'/'.$newFilename;

                    file_put_contents($localPath, $storage->get($attachment->file_path));
                    $allFiles[] = $localPath;
                    $attachment->hash =  hash('sha256', $storage->get($attachment->file_path));
                }
            }
        }

        \Log::info("Total files to include in zip: " . count($allFiles));

        // Create a hasfile for all files
        foreach ($allFiles as $file) {
            $hashFileContents = "";

            if (file_exists($file)) {
                $hashFileContents = hash_file('sha256', $file)."  ".basename($file)."\n";
                file_put_contents($tmpDir.'/hashes.txt', $hashFileContents, FILE_APPEND);
                $allFiles[] = $tmpDir.'/hashes.txt';
            }

        }

        if ($disk === 's3' || $disk === 'digitalocean') {
            // Create ZIP locally
            $zipLocalPath = $tmpDir."/audit_{$this->auditId}_data_requests.zip";
            $zip = new ZipArchive;
            if ($zip->open($zipLocalPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($allFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            // Only upload and create FileAttachment if zip file was successfully created
            if (file_exists($zipLocalPath)) {
                // Upload ZIP to S3
                $zipS3Path = $exportDir."audit_{$this->auditId}_data_requests.zip";
                \Storage::disk($disk)->put($zipS3Path, file_get_contents($zipLocalPath));

                // Create or update FileAttachment for the ZIP
                FileAttachment::updateOrCreate(
                    [
                        'audit_id' => $this->auditId,
                        'data_request_response_id' => null,
                        'file_name' => "audit_{$this->auditId}_data_requests.zip",
                    ],
                    [
                        'file_path' => $zipS3Path,
                        'file_size' => filesize($zipLocalPath),
                        'uploaded_by' => auth()->id() ?? null,
                        'description' => 'Exported audit evidence ZIP',
                    ]
                );
            }
            // Clean up
            // Remove all files in the temp directory
            $files = glob($tmpDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tmpDir);
        } else {
            // Local disk: create ZIP directly in export dir
            $exportPath = storage_path('app/private/'.$exportDir);
            if (! is_dir($exportPath)) {
                mkdir($exportPath, 0777, true);
            }
            $zipPath = $exportPath."audit_{$this->auditId}_data_requests.zip";
            $zip = new \ZipArchive;
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($allFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            // Only create FileAttachment if zip file was successfully created
            if (file_exists($zipPath)) {
                // Create or update FileAttachment for the ZIP
                FileAttachment::updateOrCreate(
                    [
                        'audit_id' => $this->auditId,
                        'data_request_response_id' => null,
                        'file_name' => "audit_{$this->auditId}_data_requests.zip",
                    ],
                    [
                        'file_path' => $exportDir."audit_{$this->auditId}_data_requests.zip",
                        'file_size' => filesize($zipPath),
                        'uploaded_by' => auth()->id() ?? null,
                        'description' => 'Exported audit evidence ZIP',
                    ]
                );
            }

            // Remove all files in the temp directory
            $files = glob($tmpDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tmpDir);
        }

        // Clear cache flag when export completes
        \Cache::forget("audit_{$this->auditId}_exporting");
        \Log::info("ExportAuditEvidenceJob completed for audit {$this->auditId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Clear cache flag on failure
        \Cache::forget("audit_{$this->auditId}_exporting");
        \Log::error("ExportAuditEvidenceJob failed for audit {$this->auditId}: " . $exception->getMessage());
    }

}
