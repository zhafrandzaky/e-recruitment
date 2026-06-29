<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Interview;
use App\Notifications\InterviewCancelled;
use App\Notifications\InterviewScheduled;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InterviewController extends Controller
{
    /**
     * POST /applications/{id}/interview — Schedule an interview (HR only).
     *
     * Per FR-015, UC-07:
     * - HR submits datetime + meeting link (manual input, any platform)
     * - System validates URL format and saves the interview record
     * - Queues notification email to applicant
     *
     * No external API calls — meeting link is provided by HR directly.
     * The interview itself happens outside this application (ADR-003, revised by ADR-024).
     */
    public function schedule(Request $request, string $applicationId): JsonResponse
    {
        $application = Application::with(['applicant', 'jobPosting'])->findOrFail($applicationId);

        // Verify application is in shortlisted status
        if ($application->status !== 'shortlisted') {
            return response()->json([
                'error' => [
                    'code' => 'APPLICATION_NOT_SHORTLISTED',
                    'message' => 'Interview hanya dapat dijadwalkan untuk pelamar yang berstatus Lolos Seleksi Berkas.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Verify no existing active interview
        $existingInterview = Interview::where('application_id', $application->id)
            ->where('status', 'scheduled')
            ->first();

        if ($existingInterview) {
            return response()->json([
                'error' => [
                    'code' => 'INTERVIEW_ALREADY_SCHEDULED',
                    'message' => 'Interview untuk lamaran ini sudah dijadwalkan. Gunakan reschedule untuk mengubah jadwal.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'meeting_link' => ['required', 'url', 'max:500'],
        ]);

        $scheduledAt = Carbon::parse($validated['scheduled_at']);
        $meetingLink = $validated['meeting_link'];
        $jobTitle = $application->jobPosting?->title ?? 'Interview';

        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_at' => $scheduledAt,
            'meeting_link' => $meetingLink,
            'status' => 'scheduled',
        ]);

        // Queue notification email (async)
        $application->applicant->notify(new InterviewScheduled([
            'job_title' => $jobTitle,
            'scheduled_at' => $scheduledAt->format('l, d F Y, H:i') . ' WIB',
            'meeting_link' => $meetingLink,
            'is_reschedule' => false,
        ]));

        return response()->json([
            'id' => $interview->id,
            'scheduled_at' => $interview->scheduled_at->toIso8601String(),
            'meeting_link' => $interview->meeting_link,
            'status' => $interview->status,
        ], Response::HTTP_CREATED);
    }

    /**
     * PATCH /applications/{id}/interview — Reschedule an existing interview (HR only).
     *
     * Per FR-016, UC-07 (extend: Reschedule):
     * - Updates the interview record (datetime and/or meeting link)
     * - Re-notifies the applicant
     */
    public function reschedule(Request $request, string $applicationId): JsonResponse
    {
        $interview = Interview::where('application_id', $applicationId)
            ->where('status', 'scheduled')
            ->with('application.applicant')
            ->firstOrFail();

        $validated = $request->validate([
            'scheduled_at' => ['sometimes', 'required', 'date', 'after:now'],
            'meeting_link' => ['sometimes', 'required', 'url', 'max:500'],
        ]);

        $application = $interview->application;
        $jobTitle = $application->jobPosting?->title ?? 'Interview';

        $updates = [];
        if (isset($validated['scheduled_at'])) {
            $updates['scheduled_at'] = Carbon::parse($validated['scheduled_at']);
        }
        if (isset($validated['meeting_link'])) {
            $updates['meeting_link'] = $validated['meeting_link'];
        }

        if (! empty($updates)) {
            $interview->update($updates);
        }

        // Determine effective values for notification
        $scheduledAt = $interview->fresh()->scheduled_at;
        $meetingLink = $interview->fresh()->meeting_link;

        // Re-notify applicant
        $application->applicant->notify(new InterviewScheduled([
            'job_title' => $jobTitle,
            'scheduled_at' => $scheduledAt->format('l, d F Y, H:i') . ' WIB',
            'meeting_link' => $meetingLink,
            'is_reschedule' => true,
        ]));

        return response()->json([
            'id' => $interview->id,
            'scheduled_at' => $interview->scheduled_at->toIso8601String(),
            'meeting_link' => $interview->meeting_link,
            'status' => $interview->status,
        ]);
    }

    /**
     * DELETE /applications/{id}/interview — Cancel an existing interview (HR only).
     *
     * Per FR-016, UC-07 (extend: Cancel):
     * - Sets the interview status to 'cancelled'
     * - Notifies the applicant
     */
    public function cancel(string $applicationId): JsonResponse
    {
        $interview = Interview::where('application_id', $applicationId)
            ->where('status', 'scheduled')
            ->with('application.applicant')
            ->firstOrFail();

        $application = $interview->application;
        $jobTitle = $application->jobPosting?->title ?? 'Interview';

        $interview->update(['status' => 'cancelled']);

        // Notify applicant
        $application->applicant->notify(new InterviewCancelled([
            'job_title' => $jobTitle,
        ]));

        return response()->json([
            'id' => $interview->id,
            'status' => $interview->status,
        ]);
    }

    /**
     * GET /applications/{id}/interview — Get interview details for an application.
     */
    public function show(string $applicationId): JsonResponse
    {
        $interview = Interview::where('application_id', $applicationId)
            ->where('status', 'scheduled')
            ->first();

        if (! $interview) {
            return response()->json([
                'error' => [
                    'code' => 'INTERVIEW_NOT_FOUND',
                    'message' => 'Tidak ada jadwal interview aktif untuk lamaran ini.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'id' => $interview->id,
            'scheduled_at' => $interview->scheduled_at->toIso8601String(),
            'meeting_link' => $interview->meeting_link,
            'status' => $interview->status,
        ]);
    }
}
