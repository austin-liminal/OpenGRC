<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class PdfHelper extends Controller
{
    public static function getPdfVersion($file): ?string
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 8);
        fclose($handle);

        if (preg_match('/%PDF-(\d+\.\d+)/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function isPdfEncrypted($file): bool
    {
        \Log::info("Checking if PDF is encrypted: $file");

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return false;
        }

        $content = fread($handle, 8192); // Read first 8KB
        fclose($handle);

        $isEncrypted = strpos($content, '/Encrypt') !== false;
        \Log::info('PDF encryption status: '.($isEncrypted ? 'Encrypted' : 'Not Encrypted'));

        return $isEncrypted;

    }

    public static function convertPdfTo14($sourceFile, $destFile): bool
    {
        // Convert a PDF to version 1.4 using ghostscript
        $cmd = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile='.escapeshellarg($destFile).' '.escapeshellarg($sourceFile);

        exec($cmd, $output, $returnVar);

        return $returnVar === 0 && file_exists($destFile);
    }

    /**
     * Convert HTML content with image tags to use base64 data URIs for DomPDF compatibility
     * This is necessary because DomPDF cannot access remote URLs or storage paths directly
     */
    public static function convertImagesToBase64($html, $disk = null)
    {
        if (empty($html)) {
            return $html;
        }

        // Use the default storage disk if none specified
        $storageDisk = $disk ?? \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'));

        // Pattern to match img tags with src attributes
        $pattern = '/<img([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i';

        $html = preg_replace_callback($pattern, function ($matches) use ($storageDisk) {
            $beforeSrc = $matches[1];
            $src = $matches[2];
            $afterSrc = $matches[3];

            // Skip if already base64
            if (strpos($src, 'data:image') === 0) {
                return $matches[0];
            }

            $base64Image = null;

            // Try different methods to get the image content
            try {
                $storagePath = null;

                // Decode HTML entities in the URL (e.g., &amp; to &)
                $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5);

                // Check if it's a remote URL (signed DigitalOcean Spaces URL or other remote image)
                $isRemoteUrl = preg_match('/^https?:\/\//i', $src);

                if ($isRemoteUrl) {
                    // Try to extract storage path from signed URLs (S3, DigitalOcean Spaces, etc.)
                    // These URLs have the format: https://bucket.region.provider.com/path/to/file.png?X-Amz-...
                    // We want to extract the path and access it directly from storage

                    // Parse URL to extract path
                    $parsedUrl = parse_url($src);
                    $urlPath = $parsedUrl['path'] ?? '';

                    // Remove leading slash and extract the storage path
                    $urlPath = ltrim($urlPath, '/');

                    // Check if this looks like a storage path (contains ssp-uploads or other known patterns)
                    if (preg_match('#(ssp-uploads/.+?)(\?|$)#', $urlPath, $pathMatches)) {
                        $storagePath = $pathMatches[1];
                        Log::info('[PdfHelper] Extracted storage path from signed URL', [
                            'url' => $src,
                            'storage_path' => $storagePath,
                        ]);

                        // Try to get from storage disk directly
                        if ($storageDisk->exists($storagePath)) {
                            $imageContent = $storageDisk->get($storagePath);
                            $mimeType = $storageDisk->mimeType($storagePath);
                            $base64Image = 'data:'.$mimeType.';base64,'.base64_encode($imageContent);

                            Log::info('[PdfHelper] Successfully converted image from storage', [
                                'storage_path' => $storagePath,
                                'mime_type' => $mimeType,
                                'size' => strlen($imageContent),
                            ]);
                        } else {
                            Log::warning('[PdfHelper] Storage path extracted but file not found', [
                                'storage_path' => $storagePath,
                            ]);
                        }
                    }

                    // If we couldn't get it from storage, try downloading (for external images)
                    if (! $base64Image) {
                        Log::info('[PdfHelper] Attempting to download remote image', ['url' => $src]);

                        // Download the image temporarily
                        $tempFile = sys_get_temp_dir().'/'.uniqid('img_', true).'.tmp';

                        // Use curl for better error handling
                        $ch = curl_init($src);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                        $imageContent = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $curlError = curl_error($ch);
                        curl_close($ch);

                        if ($imageContent !== false && $httpCode === 200) {
                            file_put_contents($tempFile, $imageContent);

                            // Get mime type
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mimeType = $finfo->file($tempFile);

                            // Convert to base64
                            $base64Image = 'data:'.$mimeType.';base64,'.base64_encode($imageContent);

                            Log::info('[PdfHelper] Successfully converted remote image', [
                                'mime_type' => $mimeType,
                                'size' => strlen($imageContent),
                            ]);

                            // Clean up temp file
                            if (file_exists($tempFile)) {
                                unlink($tempFile);
                            }
                        } else {
                            Log::warning('[PdfHelper] Failed to download remote image', [
                                'url' => $src,
                                'http_code' => $httpCode,
                                'curl_error' => $curlError,
                            ]);
                        }
                    }
                } else {
                    // Handle local storage paths
                    // Parse the source URL to extract the storage path
                    // TinyEditor typically stores images with paths like:
                    // /app/priv-storage/ssp-uploads/filename.png
                    // or /storage/ssp-uploads/filename.png

                    // Remove domain if present
                    $cleanSrc = preg_replace('/^https?:\/\/[^\/]+/', '', $src);

                    // Extract the file path after the storage prefix
                    // Handle /app/priv-storage/ prefix (private storage)
                    if (preg_match('#/app/priv-storage/(.+?)(\?.*)?$#', $cleanSrc, $pathMatches)) {
                        $storagePath = $pathMatches[1];
                    }
                    // Handle /storage/ prefix (public storage)
                    elseif (preg_match('#/storage/(.+?)(\?.*)?$#', $cleanSrc, $pathMatches)) {
                        $storagePath = $pathMatches[1];
                    }
                    // Handle direct ssp-uploads/ path
                    elseif (strpos($cleanSrc, 'ssp-uploads/') !== false) {
                        if (preg_match('#(ssp-uploads/[^?]+)#', $cleanSrc, $pathMatches)) {
                            $storagePath = $pathMatches[1];
                        }
                    }

                    // Try to get from storage
                    if ($storagePath && $storageDisk->exists($storagePath)) {
                        $imageContent = $storageDisk->get($storagePath);
                        $mimeType = $storageDisk->mimeType($storagePath);
                        $base64Image = 'data:'.$mimeType.';base64,'.base64_encode($imageContent);
                    }

                    // If not found in storage, try as absolute file path
                    if (! $base64Image && file_exists($src)) {
                        $imageContent = file_get_contents($src);
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->file($src);
                        $base64Image = 'data:'.$mimeType.';base64,'.base64_encode($imageContent);
                    }

                    // Try as public path
                    if (! $base64Image) {
                        $publicPath = public_path($cleanSrc);
                        if (file_exists($publicPath)) {
                            $imageContent = file_get_contents($publicPath);
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mimeType = $finfo->file($publicPath);
                            $base64Image = 'data:'.$mimeType.';base64,'.base64_encode($imageContent);
                        }
                    }
                }

            } catch (\Exception $e) {
                Log::warning('[PdfHelper] Failed to convert image to base64', [
                    'src' => $src,
                    'storage_path' => $storagePath ?? 'not found',
                    'error' => $e->getMessage(),
                ]);
            }

            // If we successfully converted the image, use the base64 version
            if ($base64Image) {
                return '<img'.$beforeSrc.'src="'.$base64Image.'"'.$afterSrc.'>';
            }

            // Otherwise, log and return the original tag
            Log::warning('[PdfHelper] Could not convert image to base64', [
                'src' => $src,
                'storage_path' => $storagePath ?? 'not parsed',
            ]);

            return $matches[0];
        }, $html);

        return $html;
    }

    /**
     * Merge PDF attachments with the main PDF using Ghostscript
     */
    public static function mergePdfs($mainPdfPath, $pdfAttachments, $outputPath, $disk = null)
    {
        try {
            // Collect all PDF files to merge
            $pdfFiles = [];
            $tmpFiles = [];

            // Add the main PDF first
            $pdfFiles[] = escapeshellarg($mainPdfPath);

            // Add each PDF attachment
            if ($disk !== null) {
                $storage = \Illuminate\Support\Facades\Storage::disk($disk);

                foreach ($pdfAttachments as $attachment) {
                    if ($storage->exists($attachment->file_path)) {
                        // Create a temporary file for the attachment
                        $tmpAttachmentPath = sys_get_temp_dir().'/'.uniqid().'.pdf';
                        file_put_contents($tmpAttachmentPath, $storage->get($attachment->file_path));
                        $tmpFiles[] = $tmpAttachmentPath;

                        // Verify the PDF is valid before adding
                        if (filesize($tmpAttachmentPath) > 0) {
                            $pdfFiles[] = escapeshellarg($tmpAttachmentPath);
                        } else {
                            Log::warning('[PdfHelper] Skipping empty PDF attachment', [
                                'attachment_id' => $attachment->id,
                                'file_name' => $attachment->file_name,
                            ]);
                        }
                    }
                }
            } else {
                // If no disk is specified, assume $pdfAttachments contains file paths
                foreach ($pdfAttachments as $attachmentPath) {
                    if (file_exists($attachmentPath) && filesize($attachmentPath) > 0) {
                        $pdfFiles[] = escapeshellarg($attachmentPath);
                    }
                }
            }

            // Only proceed if we have files to merge
            if (count($pdfFiles) > 1) {
                // Build the Ghostscript command
                $command = sprintf(
                    'gs -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -sOutputFile=%s %s 2>&1',
                    escapeshellarg($outputPath),
                    implode(' ', $pdfFiles)
                );

                // Execute the Ghostscript command
                $output = [];
                $returnVar = 0;
                exec($command, $output, $returnVar);

                // Check if the command was successful
                if ($returnVar !== 0) {
                    Log::error('[PdfHelper] Ghostscript command failed', [
                        'command' => $command,
                        'output' => implode("\n", $output),
                    ]);
                    throw new \Exception('Ghostscript command failed: '.implode("\n", $output));
                }

                // Verify the output file was created
                if (! file_exists($outputPath) || filesize($outputPath) == 0) {
                    Log::error('[PdfHelper] Ghostscript failed to create output file', [
                        'output_path' => $outputPath,
                    ]);
                    throw new \Exception('Ghostscript failed to create output file');
                }
            } else {
                // If only main PDF exists, just copy it
                copy($mainPdfPath, $outputPath);
            }

            // Clean up temporary files
            foreach ($tmpFiles as $tmpFile) {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('[PdfHelper] PDF merging with Ghostscript failed', [
                'main_pdf' => $mainPdfPath,
                'output_path' => $outputPath,
                'error' => $e->getMessage(),
            ]);

            // Clean up temporary files in case of error
            foreach ($tmpFiles ?? [] as $tmpFile) {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }

            // If merging fails, just copy the main PDF
            copy($mainPdfPath, $outputPath);

            return false;
        }
    }
}
