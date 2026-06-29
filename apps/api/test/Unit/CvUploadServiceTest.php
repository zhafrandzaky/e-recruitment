<?php

namespace Tests\Unit;

use App\Services\CvUploadException;
use App\Services\CvUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvUploadServiceTest extends TestCase
{
    protected CvUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->service = new CvUploadService;
    }

    public function test_stores_valid_pdf(): void
    {
        $pdf = $this->createPdfUpload();

        $result = $this->service->store($pdf);

        $this->assertArrayHasKey('cv_path', $result);
        $this->assertArrayHasKey('cv_original_filename', $result);
        $this->assertEquals('resume.pdf', $result['cv_original_filename']);
        $this->assertStringStartsWith('applications/cv/', $result['cv_path']);
        $this->assertStringEndsWith('.pdf', $result['cv_path']);
        Storage::disk('s3')->assertExists($result['cv_path']);
    }

    public function test_path_is_uuid_based_not_user_filename(): void
    {
        $pdf = $this->createPdfUpload();

        $result = $this->service->store($pdf);

        // The path should contain a UUID, not the original filename
        $this->assertStringNotContainsString('resume', $result['cv_path']);
        $this->assertMatchesRegularExpression(
            '#^applications/cv/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.pdf$#',
            $result['cv_path']
        );
    }

    public function test_rejects_non_pdf_extension(): void
    {
        $txt = UploadedFile::fake()->create('document.txt', 100);

        $this->expectException(CvUploadException::class);
        $this->expectExceptionMessage('Format file tidak didukung');

        $this->service->store($txt);
    }

    public function test_rejects_spoofed_extension_with_text_content(): void
    {
        // File named .pdf but actual content is plain text
        $spoofed = UploadedFile::fake()->createWithContent('resume.pdf', 'This is plain text, not a PDF.');

        $this->expectException(CvUploadException::class);
        $this->expectExceptionMessage('bukan dokumen PDF yang valid');

        $this->service->store($spoofed);
    }

    public function test_rejects_oversized_file(): void
    {
        // Create file > 2MB
        $oversized = UploadedFile::fake()->createWithContent('large.pdf', str_repeat('x', 3 * 1024 * 1024));

        $this->expectException(CvUploadException::class);
        $this->expectExceptionMessage('melebihi batas maksimum');

        $this->service->store($oversized);
    }

    public function test_rejects_image_masquerading_as_pdf(): void
    {
        // PNG magic bytes with .pdf extension
        $pngBytes = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A".str_repeat("\0", 200);
        $image = UploadedFile::fake()->createWithContent('resume.pdf', $pngBytes);

        $this->expectException(CvUploadException::class);
        $this->expectExceptionMessage('bukan dokumen PDF yang valid');

        $this->service->store($image);
    }

    public function test_detects_actual_mime_not_client_reported(): void
    {
        // finfo detects the real MIME type, regardless of extension
        // This test confirms the service uses finfo (FILEINFO_MIME_TYPE),
        // not the client-reported Content-Type or file extension.
        $textFileAsPdf = UploadedFile::fake()->createWithContent('evil.pdf', "#!/bin/bash\necho 'not a pdf'");

        $this->expectException(CvUploadException::class);

        $this->service->store($textFileAsPdf);
    }

    public function test_download_throws_when_file_missing(): void
    {
        $this->expectException(CvUploadException::class);
        $this->expectExceptionMessage('Dokumen tidak dapat dimuat');

        $this->service->download('applications/cv/nonexistent.pdf');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createPdfUpload(): UploadedFile
    {
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";

        return UploadedFile::fake()->createWithContent('resume.pdf', $pdfContent);
    }
}
