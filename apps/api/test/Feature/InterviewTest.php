<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\InterviewCancelled;
use App\Notifications\InterviewScheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InterviewTest extends TestCase
{
    use RefreshDatabase;

    private function createShortlistedApplication(): Application
    {
        $applicant = User::factory()->applicant()->create();
        $hr = User::factory()->hrAdmin()->create();
        $job = JobPosting::factory()->active()->create(['created_by' => $hr->id]);

        return Application::factory()->create([
            'job_posting_id' => $job->id,
            'applicant_id' => $applicant->id,
            'status' => 'shortlisted',
            'applied_at' => now(),
        ]);
    }

    // ─── Schedule Interview ─────────────────────────────────────────────────

    public function test_hr_can_schedule_interview_with_manual_meeting_link(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        $response = $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'scheduled_at', 'meeting_link', 'status'])
            ->assertJsonPath('status', 'scheduled')
            ->assertJsonPath('meeting_link', 'https://meet.google.com/abc-defg-hij');

        $this->assertDatabaseHas('interviews', [
            'application_id' => $application->id,
            'status' => 'scheduled',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        Notification::assertSentTo(
            [$application->applicant],
            InterviewScheduled::class
        );
    }

    public function test_schedule_interview_rejects_invalid_meeting_link(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        $response = $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'not-a-valid-url',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['meeting_link']);

        $this->assertDatabaseMissing('interviews', [
            'application_id' => $application->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_schedule_interview_requires_meeting_link(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        $response = $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                // meeting_link missing
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['meeting_link']);
    }

    public function test_schedule_interview_requires_shortlisted_status(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create(['created_by' => $hr->id]);

        $application = Application::factory()->create([
            'job_posting_id' => $job->id,
            'applicant_id' => $applicant->id,
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'APPLICATION_NOT_SHORTLISTED');
    }

    public function test_cannot_schedule_duplicate_interview(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        // Schedule first
        $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ])
            ->assertCreated();

        // Try to schedule second
        $response = $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://zoom.us/j/123456',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'INTERVIEW_ALREADY_SCHEDULED');
    }

    public function test_applicant_cannot_schedule_interview(): void
    {
        $application = $this->createShortlistedApplication();

        $response = $this->actingAs($application->applicant)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ]);

        $response->assertForbidden();
    }

    // ─── Reschedule Interview ────────────────────────────────────────────────

    public function test_hr_can_reschedule_interview(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        // Schedule first
        $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ]);

        // Reschedule
        $newDate = now()->addDays(5)->format('Y-m-d H:i:s');
        $response = $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => $newDate,
            ]);

        $response->assertOk();

        // Notification should be queued for reschedule
        Notification::assertSentTo(
            [$application->applicant],
            InterviewScheduled::class,
            function ($notification) {
                return $notification->data['is_reschedule'] === true;
            }
        );
    }

    public function test_hr_can_reschedule_interview_with_new_link(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ]);

        $response = $this->actingAs($hr)
            ->patchJson("/api/applications/{$application->id}/interview", [
                'meeting_link' => 'https://zoom.us/j/999999',
            ]);

        $response->assertOk()
            ->assertJsonPath('meeting_link', 'https://zoom.us/j/999999');
    }

    // ─── Cancel Interview ────────────────────────────────────────────────────

    public function test_hr_can_cancel_interview(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        // Schedule first
        $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ]);

        // Cancel
        $response = $this->actingAs($hr)
            ->deleteJson("/api/applications/{$application->id}/interview");

        $response->assertOk();

        $this->assertDatabaseHas('interviews', [
            'application_id' => $application->id,
            'status' => 'cancelled',
        ]);

        Notification::assertSentTo(
            [$application->applicant],
            InterviewCancelled::class
        );
    }

    // ─── Get Interview Detail ────────────────────────────────────────────────

    public function test_can_get_interview_detail(): void
    {
        Notification::fake();

        $application = $this->createShortlistedApplication();
        $hr = User::factory()->hrAdmin()->create();

        $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            ]);

        $response = $this->actingAs($hr)
            ->getJson("/api/applications/{$application->id}/interview");

        $response->assertOk()
            ->assertJsonStructure(['id', 'scheduled_at', 'meeting_link', 'status'])
            ->assertJsonPath('status', 'scheduled');
    }
}
