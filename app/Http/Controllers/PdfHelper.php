<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PdfHelper extends Controller
{
    public static function getPdfVersion($file): string | null
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
        \Log::info("PDF encryption status: " . ($isEncrypted ? 'Encrypted' : 'Not Encrypted'));
        return $isEncrypted;

    }

    public static function convertPdfTo14($sourceFile, $destFile): bool
    {
        // Convert a PDF to version 1.4 using ghostscript
        $cmd = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($destFile) . " " . escapeshellarg($sourceFile);

        exec($cmd, $output, $returnVar);

        return ($returnVar === 0 && file_exists($destFile));
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
                if (!file_exists($outputPath) || filesize($outputPath) == 0) {
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
