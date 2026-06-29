<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles CV file validation and storage.
 *
 * Per docs/SECURITY.md Section 4 and docs/ARCHITECTURE.md Section 9:
 * - Files validated by actual MIME type (not extension or client-reported Content-Type)
 * - PDF only, max 2MB
 * - Stored in S3-compatible storage via Flysystem
 * - System-generated storage key (UUID-based), original filename stored separately
 */
class CvUploadService
{
    private const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2MB
    private const ALLOWED_MIME = 'application/pdf';
    private const ALLOWED_EXTENSION = 'pdf';
    private const STORAGE_DISK = 's3';
    private const STORAGE_PREFIX = 'applications/cv';

    /**
     * Validate and store a CV file.
     *
     * @param UploadedFile $file The uploaded file from the request
     * @return array{cv_path: string, cv_original_filename: string}
     * @throws CvUploadException
     */
    public function store(UploadedFile $file): array
    {
        $this->validateFile($file);

        $originalName = $file->getClientOriginalName();
        $storageKey = self::STORAGE_PREFIX . '/' . Str::uuid()->toString() . '.pdf';

        // Store in S3-compatible storage via Flysystem
        Storage::disk(self::STORAGE_DISK)->putFileAs(
            dirname($storageKey),
            $file,
            basename($storageKey),
        );

        return [
            'cv_path' => $storageKey,
            'cv_original_filename' => $originalName,
        ];
    }

    /**
     * Get a temporary download URL for a CV file.
     *
     * The URL is time-limited to prevent indefinite public access.
     */
    public function temporaryUrl(string $path, int $minutes = 5): string
    {
        $disk = Storage::disk(self::STORAGE_DISK);

        // Some S3-compatible providers (MinIO) don't support temporaryUrl natively
        // via the S3 driver in all configurations, so we check existence first.
        if (! $disk->exists($path)) {
            throw new CvUploadException('Dokumen tidak dapat dimuat. File tidak ditemukan dalam penyimpanan.');
        }

        return $disk->temporaryUrl($path, now()->addMinutes($minutes));
    }

    /**
     * Stream a CV file for download with proper Content-Type headers.
     */
    public function download(string $path): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $disk = Storage::disk(self::STORAGE_DISK);

        if (! $disk->exists($path)) {
            throw new CvUploadException('Dokumen tidak dapat dimuat. File tidak ditemukan dalam penyimpanan.');
        }

        return $disk->download($path, null, [
            'Content-Type' => self::ALLOWED_MIME,
        ]);
    }

    /**
     * Validate the uploaded file against all security rules.
     *
     * This is the authoritative server-side check — client-side validation
     * is for UX only and is never trusted as a security boundary.
     */
    private function validateFile(UploadedFile $file): void
    {
        // Size check (server-side — client-side limits are bypassable)
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new CvUploadException(
                'Ukuran file melebihi batas maksimum 2MB.',
                'cv',
                'size'
            );
        }

        // Extension check (first pass, quick rejection)
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== self::ALLOWED_EXTENSION) {
            throw new CvUploadException(
                'Format file tidak didukung. Hanya file PDF yang diterima.',
                'cv',
                'format'
            );
        }

        // Actual MIME type inspection — the critical security check.
        // We use finfo (PHP's Fileinfo extension) for genuine content-based
        // MIME detection, not the client-reported Content-Type or file extension.
        $detectedMime = $this->detectActualMimeType($file);

        if ($detectedMime !== self::ALLOWED_MIME) {
            throw new CvUploadException(
                'File yang diunggah bukan dokumen PDF yang valid. Sistem mendeteksi tipe file: ' . $detectedMime,
                'cv',
                'mime_spoof'
            );
        }
    }

    /**
     * Detect the actual MIME type of a file using PHP's finfo (Fileinfo).
     *
     * finfo performs content-based MIME detection by reading the file's
     * magic bytes, not relying on the file extension or HTTP Content-Type.
     * This is the only reliable way to detect the true file type.
     */
    private function detectActualMimeType(UploadedFile $file): string
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
    }
}
