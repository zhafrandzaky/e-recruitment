<?php

namespace App\Http\Controllers;

use App\Jobs\SendApplicationNotification;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\JobPosting;
use App\Services\CvUploadException;
use App\Services\CvUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ApplicationController extends Controller
{
    /**
     * POST /jobs/{id}/applications — Submit an application for a job posting.
     *
     * Per UC-03, FR-007, FR-008, FR-009:
     * - Multipart: CV file + form data fields
     * - Server-side validation: PDF-only MIME check, 2MB limit, all fields required
     * - CV stored in S3-compatible storage via Flysystem
     * - Status defaults to 'pending'
     * - Notification job is queued (placeholder in Phase 2, real email in Phase 3)
     */
    public function submit(Request $request, string $jobId, CvUploadService $cvService): JsonResponse
    {
        // Verify the job posting exists and is active
        $job = JobPosting::active()->findOrFail($jobId);

        // Validate form data
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:1000'],
            'cv' => ['required', 'file'],
        ]);

        // Validate and store CV — this throws CvUploadException on failure
        try {
            $cvResult = $cvService->store($request->file('cv'));
        } catch (CvUploadException $e) {
            return response()->json([
                'error' => [
                    'code' => 'CV_VALIDATION_FAILED',
                    'message' => $e->getMessage(),
                    'fields' => [$e->field => [$e->getMessage()]],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application = Application::create([
            'job_posting_id' => $job->id,
            'applicant_id' => $request->user()->id,
            'cv_path' => $cvResult['cv_path'],
            'cv_original_filename' => $cvResult['cv_original_filename'],
            'additional_data' => [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ],
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        // Queue notification (placeholder — Phase 3 will send real email)
        SendApplicationNotification::dispatch($application, 'submitted');

        return response()->json($this->formatApplication($application), Response::HTTP_CREATED);
    }

    /**
     * GET /applications/me — Applicant's own application history.
     *
     * Per FR-010, UC-04:
     * - Shows all of the authenticated applicant's applications
     * - Ownership enforced: only ever returns current user's data
     */
    public function myApplications(Request $request): JsonResponse
    {
        $applications = Application::with('jobPosting')
            ->where('applicant_id', $request->user()->id)
            ->orderByDesc('applied_at')
            ->get();

        return response()->json([
            'data' => $applications->map(fn (Application $app) => $this->formatApplication($app)),
        ]);
    }

    /**
     * GET /applications/{id} — Application detail.
     *
     * Applicant can view own; HR can view any.
     * Ownership enforced per docs/SECURITY.md Section 3.2.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $application = Application::with(['jobPosting', 'statusHistory'])->findOrFail($id);

        // Ownership check: applicant only sees own, HR sees all
        if ($request->user()->isApplicant() && $application->applicant_id !== $request->user()->id) {
            // Return 404 to avoid leaking existence of other users' applications
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json($this->formatApplication($application, true));
    }

    /**
     * GET /jobs/{id}/applications — List applicants for a job posting (HR only).
     *
     * Per FR-011, UC-06.
     */
    public function listByJob(Request $request, string $jobId): JsonResponse
    {
        $job = JobPosting::findOrFail($jobId);

        $applications = Application::with('applicant')
            ->where('job_posting_id', $job->id)
            ->orderByDesc('applied_at')
            ->get();

        return response()->json([
            'data' => $applications->map(fn (Application $app) => $this->formatApplicationForHr($app)),
        ]);
    }

    /**
     * GET /applications/{id}/cv — Download/view CV file.
     *
     * Per FR-012, UC-06.
     * Applicant can access own CV; HR can access any.
     * Handles the "file fails to load/corrupted" failure case with a clear warning.
     */
    public function downloadCv(Request $request, string $id, CvUploadService $cvService): mixed
    {
        $application = Application::findOrFail($id);

        // Ownership check
        if ($request->user()->isApplicant() && $application->applicant_id !== $request->user()->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! $application->cv_path) {
            return response()->json([
                'error' => [
                    'code' => 'CV_NOT_FOUND',
                    'message' => 'Dokumen tidak dapat dimuat. File CV tidak ditemukan — mohon hubungi pelamar untuk mengunggah ulang.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            return $cvService->download($application->cv_path);
        } catch (CvUploadException $e) {
            return response()->json([
                'error' => [
                    'code' => 'CV_UNREADABLE',
                    'message' => 'Dokumen tidak dapat dimuat. File mungkin rusak atau tidak tersedia — mohon hubungi pelamar untuk mengunggah ulang.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * PATCH /applications/{id}/status — Update application status (HR only).
     *
     * Per FR-013, UC-06:
     * - Changes status via dropdown
     * - Creates application_status_history entry on every change
     * - Queues notification job (placeholder in Phase 2, real email in Phase 3)
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $application = Application::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'shortlisted', 'rejected'])],
        ]);

        $previousStatus = $application->status;
        $newStatus = $validated['status'];

        // No-op if status hasn't changed
        if ($previousStatus === $newStatus) {
            return response()->json($this->formatApplication($application));
        }

        $application->update(['status' => $newStatus]);

        // Create status history entry — required for Phase 5 reporting
        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
        ]);

        // Queue notification (placeholder — Phase 3 will send real email)
        SendApplicationNotification::dispatch($application, 'status_changed', [
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
        ]);

        return response()->json($this->formatApplication($application->fresh()));
    }

    // ─── Response formatters ────────────────────────────────────────────────

    private function formatApplication(Application $application, bool $includeStatusHistory = false): array
    {
        $data = [
            'id' => $application->id,
            'job_posting_id' => $application->job_posting_id,
            'status' => $application->status,
            'cv_original_filename' => $application->cv_original_filename,
            'additional_data' => $application->additional_data,
            'applied_at' => $application->applied_at?->toIso8601String(),
            'created_at' => $application->created_at->toIso8601String(),
            'updated_at' => $application->updated_at->toIso8601String(),
        ];

        if ($application->relationLoaded('jobPosting')) {
            $data['job'] = [
                'id' => $application->jobPosting->id,
                'title' => $application->jobPosting->title,
                'location' => $application->jobPosting->location,
                'status' => $application->jobPosting->status,
            ];
        }

        if ($includeStatusHistory && $application->relationLoaded('statusHistory')) {
            $data['status_history'] = $application->statusHistory->map(fn ($h) => [
                'id' => $h->id,
                'previous_status' => $h->previous_status,
                'new_status' => $h->new_status,
                'changed_at' => $h->changed_at->toIso8601String(),
            ]);
        }

        return $data;
    }

    private function formatApplicationForHr(Application $application): array
    {
        $data = $this->formatApplication($application);

        if ($application->relationLoaded('applicant')) {
            $data['applicant'] = [
                'id' => $application->applicant->id,
                'name' => $application->applicant->name,
                'email' => $application->applicant->email,
            ];
        }

        return $data;
    }
}
