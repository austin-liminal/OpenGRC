<?php

namespace App\Http\Controllers;

class PdfController extends Controller
{
    public static function getPdfVersion(string $filePath): ?string
    {
        if (! is_readable($filePath)) {
            throw new InvalidArgumentException("File not found or not readable: {$filePath}");
        }

        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            throw new RuntimeException("Unable to open file: {$filePath}");
        }

        // Read the first 20 bytes, more than enough to include the header
        $header = fread($handle, 20);
        fclose($handle);

        if (preg_match('/%PDF-(\d\.\d)/', $header, $matches)) {
            return $matches[1]; // e.g., "1.4", "1.7"
        }

        return null; // Not a valid PDF header
    }

    public static function convertPdfTo14(string $filePath): bool
    {
        // Reuse the version-check method
        $version = PdfController::getPdfVersion($filePath);

        if ($version === null) {
            throw new RuntimeException("File does not appear to be a valid PDF: {$filePath}");
        }

        // If version <= 1.4, nothing to do
        if (version_compare($version, '1.5', '<')) {
            return false; // No conversion performed
        }

        $GS_PATH = setting('ghostscript_path', '/usr/bin/gs');
        if (! file_exists($GS_PATH) || ! is_executable($GS_PATH)) {
            return false; // Ghostscript not available
            Log::error("Ghostscript not found or not executable at: {$GS_PATH}");
        }

        // Paths
        $dir = dirname($filePath);
        $filename = basename($filePath, '.pdf');
        $original = $dir.DIRECTORY_SEPARATOR.$filename.'-original.pdf';
        $tmpFile = $filePath.'.tmp.pdf';

        // Rename the original first (backup)
        if (! rename($filePath, $original)) {
            throw new RuntimeException("Failed to backup original file to: {$original}");
        }

        // Build the Ghostscript command
        $cmd = sprintf(
            '/usr/bin/gs -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s 2>&1',
            escapeshellarg($filePath), // overwrite target
            escapeshellarg($original)  // use the backup as input
        );

        // Execute and capture output
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            // Restore original if conversion failed
            rename($original, $filePath);
            throw new RuntimeException('Ghostscript failed: '.implode("\n", $output));
        }

        return true; // Conversion performed, original saved separately
    }
}
