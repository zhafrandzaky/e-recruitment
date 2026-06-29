<?php

namespace Tests\Feature;

use App\Jobs\SendApplicationNotification;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\JobPosting;
use App\Models\User;
use App\Services\CvUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Use a fake S3 disk for all tests to avoid needing MinIO running
        Storage::fake('s3');
    }

    // ─── Helper ─────────────────────────────────────────────────────────────

    private function createPdfUpload(int $sizeKb = 100): UploadedFile
    {
        // Create a minimal valid PDF with proper magic bytes
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";
        $content = str_pad($pdfContent, $sizeKb * 1024, ' ');

        return UploadedFile::fake()->createWithContent('resume.pdf', $content);
    }

    private function createTextFileUpload(string $filename = 'resume.pdf'): UploadedFile
    {
        // File has .pdf extension but contains plain text (MIME spoofing attempt)
        return UploadedFile::fake()->createWithContent($filename, 'This is not a PDF file, it is plain text.');
    }

    private function createOversizedPdfUpload(): UploadedFile
    {
        // Create a PDF larger than 2MB
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \ntrailer<</Size 4>>\nstartxref\n190\n%%EOF";

        return UploadedFile::fake()->createWithContent('large.pdf', str_pad($pdfContent, 3 * 1024 * 1024, "\0"));
    }

    // ─── Task 2: CV Upload Validation (FR-007) ───────────────────────────────

    public function test_applicant_can_submit_application_with_valid_pdf(): void
    {
        Queue::fake();
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();
        $pdf = $this->createPdfUpload();

        $response = $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 123, Jakarta',
                'cv' => $pdf,
            ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('cv_original_filename', 'resume.pdf')
            ->assertJsonStructure(['id', 'status', 'cv_original_filename', 'additional_data', 'applied_at']);

        $this->assertDatabaseHas('applications', [
            'applicant_id' => $applicant->id,
            'job_posting_id' => $job->id,
            'status' => 'pending',
        ]);

        // Assert CV was stored in S3
        $application = Application::first();
        Storage::disk('s3')->assertExists($application->cv_path);

        // Assert notification was queued
        Queue::assertPushed(SendApplicationNotification::class);
    }

    public function test_non_pdf_file_rejected_even_with_pdf_extension(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();
        // File has .pdf extension but is plain text — the critical spoofing test
        $fakePdf = $this->createTextFileUpload('resume.pdf');

        $response = $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Test User',
                'phone' => '081234567890',
                'address' => 'Address',
                'cv' => $fakePdf,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'CV_VALIDATION_FAILED');

        $this->assertDatabaseEmpty('applications');
    }

    public function test_file_with_wrong_extension_rejected(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();
        $txtFile = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Test User',
                'phone' => '081234567890',
                'address' => 'Address',
                'cv' => $txtFile,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'CV_VALIDATION_FAILED');

        $this->assertDatabaseEmpty('applications');
    }

    public function test_oversized_file_rejected(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();
        $largePdf = $this->createOversizedPdfUpload();

        $response = $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Test User',
                'phone' => '081234567890',
                'address' => 'Address',
                'cv' => $largePdf,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'CV_VALIDATION_FAILED');

        $this->assertDatabaseEmpty('applications');
    }

    public function test_image_file_with_pdf_extension_rejected(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();
        // PNG image bytes renamed to .pdf — another spoofing variant
        // PNG magic bytes: 89 50 4E 47 0D 0A 1A 0A
        $pngBytes = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A".str_repeat("\0", 200);
        $jpgAsPdf = UploadedFile::fake()->createWithContent('avatar.pdf', $pngBytes);

        $response = $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Test User',
                'phone' => '081234567890',
                'address' => 'Address',
                'cv' => $jpgAsPdf,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'CV_VALIDATION_FAILED');

        $this->assertDatabaseEmpty('applications');
    }

    // ─── Task 3: Application Submission (FR-008, FR-009) ──────────────────────

    public function test_submission_requires_all_fields(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();

        $response = $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", []);

        $response->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['name', 'phone', 'address', 'cv']]);
    }

    public function test_submission_rejected_for_closed_job(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->closed()->create();
        $pdf = $this->createPdfUpload();

        $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Test',
                'phone' => '123',
                'address' => 'Addr',
                'cv' => $pdf,
            ])->assertNotFound();
    }

    public function test_submission_rejected_for_draft_job(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->draft()->create();
        $pdf = $this->createPdfUpload();

        $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Test',
                'phone' => '123',
                'address' => 'Addr',
                'cv' => $pdf,
            ])->assertNotFound();
    }

    public function test_hr_cannot_submit_application(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $job = JobPosting::factory()->active()->create();
        $pdf = $this->createPdfUpload();

        // HR has role hr_admin, not applicant — route middleware should block
        $this->actingAs($hr)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Test',
                'phone' => '123',
                'address' => 'Addr',
                'cv' => $pdf,
            ])->assertForbidden();
    }

    public function test_unauthenticated_cannot_submit_application(): void
    {
        $job = JobPosting::factory()->active()->create();
        $pdf = $this->createPdfUpload();

        $this->postJson("/api/jobs/{$job->id}/applications", [
            'name' => 'Test',
            'phone' => '123',
            'address' => 'Addr',
            'cv' => $pdf,
        ])->assertUnauthorized();
    }

    public function test_submission_stores_additional_data_as_jsonb(): void
    {
        Queue::fake();
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();
        $pdf = $this->createPdfUpload();

        $this->actingAs($applicant)
            ->postJson("/api/jobs/{$job->id}/applications", [
                'name' => 'Citra Dewi',
                'phone' => '085678901234',
                'address' => 'Jl. Sudirman No. 45, Bandung',
                'cv' => $pdf,
            ])->assertCreated();

        $app = Application::first();
        $this->assertEquals('Citra Dewi', $app->additional_data['name']);
        $this->assertEquals('085678901234', $app->additional_data['phone']);
        $this->assertEquals('Jl. Sudirman No. 45, Bandung', $app->additional_data['address']);
    }

    // ─── Task 4: Application Status Viewing — Applicant side (FR-010) ────────

    public function test_applicant_can_view_own_applications(): void
    {
        $applicant = User::factory()->applicant()->create();
        Application::factory()->count(3)->create(['applicant_id' => $applicant->id]);
        Application::factory()->count(2)->create(); // other applicant

        $response = $this->actingAs($applicant)->getJson('/api/applications/me');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'job', 'status', 'applied_at']]]);
    }

    public function test_applicant_cannot_access_other_applicants_application_by_id(): void
    {
        $applicantA = User::factory()->applicant()->create();
        $applicantB = User::factory()->applicant()->create();
        $appB = Application::factory()->create(['applicant_id' => $applicantB->id]);

        // Applicant A tries to directly access Applicant B's application via ID
        $this->actingAs($applicantA)
            ->getJson("/api/applications/{$appB->id}")
            ->assertNotFound(); // 404, not 403 — don't leak existence
    }

    public function test_applicant_cannot_access_other_applicants_cv(): void
    {
        $applicantA = User::factory()->applicant()->create();
        $applicantB = User::factory()->applicant()->create();
        $appB = Application::factory()->create([
            'applicant_id' => $applicantB->id,
            'cv_path' => 'applications/cv/test.pdf',
        ]);

        $this->actingAs($applicantA)
            ->getJson("/api/applications/{$appB->id}/cv")
            ->assertNotFound();
    }

    public function test_unauthenticated_cannot_access_my_applications(): void
    {
        $this->getJson('/api/applications/me')->assertUnauthorized();
    }

    // ─── Task 5: Screening — HR side (FR-011, FR-012, FR-013) ───────────────

    public function test_hr_can_list_applicants_for_job(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $job = JobPosting::factory()->active()->create();
        Application::factory()->count(4)->create(['job_posting_id' => $job->id]);
        Application::factory()->count(2)->create(); // different job

        $response = $this->actingAs($hr)
            ->getJson("/api/jobs/{$job->id}/applications");

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure(['data' => [['id', 'status', 'applicant' => ['name', 'email']]]]);
    }

    public function test_applicant_cannot_list_applicants_for_job(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();

        $this->actingAs($applicant)
            ->getJson("/api/jobs/{$job->id}/applications")
            ->assertForbidden();
    }

    public function test_hr_can_download_applicant_cv(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $pdf = $this->createPdfUpload();
        $cvService = app(CvUploadService::class);

        // Actually store a real PDF so download test works
        $cvResult = $cvService->store($pdf);
        $application = Application::factory()->create([
            'cv_path' => $cvResult['cv_path'],
            'cv_original_filename' => $cvResult['cv_original_filename'],
        ]);

        // CV download returns a streamed response, not JSON
        $response = $this->actingAs($hr)
            ->get("/api/applications/{$application->id}/cv");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_cv_download_handles_missing_file(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $application = Application::factory()->create([
            'cv_path' => null,
            'cv_original_filename' => null,
        ]);

        $this->actingAs($hr)
            ->getJson("/api/applications/{$application->id}/cv")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'CV_NOT_FOUND');
    }

    public function test_hr_can_update_application_status(): void
    {
        Queue::fake();
        $hr = User::factory()->hrAdmin()->create();
        $application = Application::factory()->pending()->create();

        $response = $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/status", [
                'status' => 'shortlisted',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'shortlisted');

        $this->assertEquals('shortlisted', $application->fresh()->status);
    }

    public function test_status_change_creates_history_entry(): void
    {
        Queue::fake();
        $hr = User::factory()->hrAdmin()->create();
        $application = Application::factory()->pending()->create();

        $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/status", [
                'status' => 'shortlisted',
            ])->assertOk();

        $this->assertDatabaseHas('application_status_history', [
            'application_id' => $application->id,
            'previous_status' => 'pending',
            'new_status' => 'shortlisted',
            'changed_by' => $hr->id,
        ]);
    }

    public function test_status_change_queues_notification(): void
    {
        Queue::fake();
        $hr = User::factory()->hrAdmin()->create();
        $application = Application::factory()->pending()->create();

        $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/status", [
                'status' => 'rejected',
            ])->assertOk();

        Queue::assertPushed(SendApplicationNotification::class, function ($job) {
            return $job->event === 'status_changed'
                && $job->context['previous_status'] === 'pending'
                && $job->context['new_status'] === 'rejected';
        });
    }

    public function test_status_change_rejects_invalid_status(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $application = Application::factory()->pending()->create();

        $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/status", [
                'status' => 'accepted', // not in enum
            ])->assertUnprocessable();
    }

    public function test_status_change_noop_does_not_create_history(): void
    {
        Queue::fake();
        $hr = User::factory()->hrAdmin()->create();
        $application = Application::factory()->pending()->create();

        $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/status", [
                'status' => 'pending', // same as current
            ])->assertOk();

        // No history entry should be created for a no-op
        $this->assertDatabaseMissing('application_status_history', [
            'application_id' => $application->id,
        ]);

        // No notification should be queued for a no-op
        Queue::assertNotPushed(SendApplicationNotification::class);
    }

    public function test_applicant_cannot_update_status(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = Application::factory()->pending()->create(['applicant_id' => $applicant->id]);

        $this->actingAs($applicant)
            ->patchJson("/api/applications/{$application->id}/status", [
                'status' => 'shortlisted',
            ])->assertForbidden();
    }

    public function test_status_history_entries_accumulate_on_multiple_changes(): void
    {
        Queue::fake();
        $hr = User::factory()->hrAdmin()->create();
        $application = Application::factory()->pending()->create();

        // pending -> shortlisted
        $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/status", ['status' => 'shortlisted'])
            ->assertOk();

        // shortlisted -> rejected
        $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/status", ['status' => 'rejected'])
            ->assertOk();

        $this->assertEquals(2, ApplicationStatusHistory::where('application_id', $application->id)->count());
    }

    // ─── HR-only enforcement ─────────────────────────────────────────────────

    public function test_hr_only_endpoints_reject_unauthenticated(): void
    {
        $job = JobPosting::factory()->active()->create();
        $app = Application::factory()->create();

        $this->getJson("/api/jobs/{$job->id}/applications")->assertUnauthorized();
        $this->patchJson("/api/applications/{$app->id}/status", ['status' => 'shortlisted'])->assertUnauthorized();
    }
}
