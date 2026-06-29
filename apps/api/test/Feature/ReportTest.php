<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 5 reporting endpoints (FR-018, docs/API.md Section 7).
 *
 * These tests seed deterministic data and assert the *mathematically correct*
 * aggregate numbers — not merely that an endpoint returns 200.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function hr(): User
    {
        return User::factory()->hrAdmin()->create();
    }

    /**
     * Find the applicant count for a given job in the overview payload.
     */
    private function countForJob(array $applicantsPerJob, string $jobId): ?int
    {
        foreach ($applicantsPerJob as $row) {
            if ($row['job_id'] === $jobId) {
                return $row['count'];
            }
        }

        return null;
    }

    // ─── HR-only access enforcement ──────────────────────────────────────────

    public function test_overview_rejects_unauthenticated(): void
    {
        $this->getJson('/api/reports/overview')->assertUnauthorized();
    }

    public function test_overview_rejects_applicant(): void
    {
        $applicant = User::factory()->applicant()->create();

        $this->actingAs($applicant)
            ->getJson('/api/reports/overview')
            ->assertForbidden();
    }

    public function test_job_funnel_rejects_unauthenticated(): void
    {
        $job = JobPosting::factory()->active()->create();

        $this->getJson("/api/reports/jobs/{$job->id}/funnel")->assertUnauthorized();
    }

    public function test_job_funnel_rejects_applicant(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();

        $this->actingAs($applicant)
            ->getJson("/api/reports/jobs/{$job->id}/funnel")
            ->assertForbidden();
    }

    // ─── Applicants per job ──────────────────────────────────────────────────

    public function test_applicants_per_job_returns_correct_counts(): void
    {
        $jobA = JobPosting::factory()->active()->create(['title' => 'Job A']);
        $jobB = JobPosting::factory()->active()->create(['title' => 'Job B']);
        $jobC = JobPosting::factory()->active()->create(['title' => 'Job C (no applicants)']);

        Application::factory()->count(3)->create(['job_posting_id' => $jobA->id]);
        Application::factory()->count(2)->create(['job_posting_id' => $jobB->id]);
        // jobC intentionally has zero applications.

        $response = $this->actingAs($this->hr())
            ->getJson('/api/reports/overview')
            ->assertOk();

        $perJob = $response->json('applicants_per_job');

        $this->assertSame(3, $this->countForJob($perJob, $jobA->id));
        $this->assertSame(2, $this->countForJob($perJob, $jobB->id));
        // A zero-applicant posting must still appear, with count 0.
        $this->assertSame(0, $this->countForJob($perJob, $jobC->id));
    }

    public function test_applicants_per_job_excludes_soft_deleted_applications(): void
    {
        $job = JobPosting::factory()->active()->create();
        Application::factory()->count(2)->create(['job_posting_id' => $job->id]);
        $deleted = Application::factory()->create(['job_posting_id' => $job->id]);
        $deleted->delete(); // soft delete

        $response = $this->actingAs($this->hr())
            ->getJson('/api/reports/overview')
            ->assertOk();

        $this->assertSame(2, $this->countForJob($response->json('applicants_per_job'), $job->id));
    }

    // ─── Funnel ──────────────────────────────────────────────────────────────

    public function test_funnel_counts_each_status_correctly(): void
    {
        $job = JobPosting::factory()->active()->create();
        Application::factory()->count(4)->pending()->create(['job_posting_id' => $job->id]);
        Application::factory()->count(3)->shortlisted()->create(['job_posting_id' => $job->id]);
        Application::factory()->count(2)->rejected()->create(['job_posting_id' => $job->id]);
        Application::factory()->count(1)->hired()->create(['job_posting_id' => $job->id]);

        $this->actingAs($this->hr())
            ->getJson('/api/reports/overview')
            ->assertOk()
            ->assertJsonPath('funnel.pending', 4)
            ->assertJsonPath('funnel.shortlisted', 3)
            ->assertJsonPath('funnel.rejected', 2)
            ->assertJsonPath('funnel.hired', 1);
    }

    // ─── Average time-to-hire ────────────────────────────────────────────────

    public function test_avg_time_to_hire_is_average_of_days_to_first_hire(): void
    {
        $hr = $this->hr();

        // Two jobs both created on the same date; one applicant hired 10 days
        // later, the other 20 days later → average should be exactly 15 days.
        $created = Carbon::parse('2026-01-01 00:00:00');

        $jobA = JobPosting::factory()->active()->create(['created_at' => $created]);
        $appA = Application::factory()->hired()->create(['job_posting_id' => $jobA->id]);
        ApplicationStatusHistory::factory()->create([
            'application_id' => $appA->id,
            'previous_status' => 'shortlisted',
            'new_status' => 'hired',
            'changed_by' => $hr->id,
            'changed_at' => $created->copy()->addDays(10),
        ]);

        $jobB = JobPosting::factory()->active()->create(['created_at' => $created]);
        $appB = Application::factory()->hired()->create(['job_posting_id' => $jobB->id]);
        ApplicationStatusHistory::factory()->create([
            'application_id' => $appB->id,
            'previous_status' => 'shortlisted',
            'new_status' => 'hired',
            'changed_by' => $hr->id,
            'changed_at' => $created->copy()->addDays(20),
        ]);

        $value = $this->actingAs($hr)
            ->getJson('/api/reports/overview')
            ->assertOk()
            ->json('avg_time_to_hire_days');

        $this->assertEqualsWithDelta(15.0, $value, 0.001);
    }

    public function test_avg_time_to_hire_uses_earliest_hire_when_multiple(): void
    {
        $hr = $this->hr();
        $created = Carbon::parse('2026-01-01 00:00:00');

        $job = JobPosting::factory()->active()->create(['created_at' => $created]);
        $app = Application::factory()->hired()->create(['job_posting_id' => $job->id]);

        // Two 'hired' transitions; the metric must use the earliest (day 5).
        ApplicationStatusHistory::factory()->create([
            'application_id' => $app->id,
            'previous_status' => 'shortlisted',
            'new_status' => 'hired',
            'changed_by' => $hr->id,
            'changed_at' => $created->copy()->addDays(5),
        ]);
        ApplicationStatusHistory::factory()->create([
            'application_id' => $app->id,
            'previous_status' => 'rejected',
            'new_status' => 'hired',
            'changed_by' => $hr->id,
            'changed_at' => $created->copy()->addDays(25),
        ]);

        $value = $this->actingAs($hr)
            ->getJson('/api/reports/overview')
            ->assertOk()
            ->json('avg_time_to_hire_days');

        $this->assertEqualsWithDelta(5.0, $value, 0.001);
    }

    public function test_avg_time_to_hire_is_null_when_no_hires(): void
    {
        $job = JobPosting::factory()->active()->create();
        Application::factory()->count(2)->shortlisted()->create(['job_posting_id' => $job->id]);

        $this->actingAs($this->hr())
            ->getJson('/api/reports/overview')
            ->assertOk()
            ->assertJsonPath('avg_time_to_hire_days', null);
    }

    // ─── Per-job funnel (scoping + not-found) ────────────────────────────────

    public function test_job_funnel_is_scoped_to_one_posting(): void
    {
        $jobA = JobPosting::factory()->active()->create(['title' => 'Posting A']);
        $jobB = JobPosting::factory()->active()->create(['title' => 'Posting B']);

        Application::factory()->count(2)->pending()->create(['job_posting_id' => $jobA->id]);
        Application::factory()->count(1)->hired()->create(['job_posting_id' => $jobA->id]);

        // jobB has a very different distribution — none of it must leak into A.
        Application::factory()->count(5)->rejected()->create(['job_posting_id' => $jobB->id]);

        $this->actingAs($this->hr())
            ->getJson("/api/reports/jobs/{$jobA->id}/funnel")
            ->assertOk()
            ->assertJsonPath('job_id', $jobA->id)
            ->assertJsonPath('job_title', 'Posting A')
            ->assertJsonPath('funnel.pending', 2)
            ->assertJsonPath('funnel.shortlisted', 0)
            ->assertJsonPath('funnel.rejected', 0) // jobB's 5 rejected must NOT appear here
            ->assertJsonPath('funnel.hired', 1)
            ->assertJsonPath('total', 3);
    }

    public function test_job_funnel_returns_404_for_missing_job(): void
    {
        $this->actingAs($this->hr())
            ->getJson('/api/reports/jobs/'.fake()->uuid().'/funnel')
            ->assertNotFound();
    }

    public function test_job_funnel_returns_404_for_soft_deleted_job(): void
    {
        $job = JobPosting::factory()->active()->create();
        $job->delete(); // soft delete

        $this->actingAs($this->hr())
            ->getJson("/api/reports/jobs/{$job->id}/funnel")
            ->assertNotFound();
    }
}
