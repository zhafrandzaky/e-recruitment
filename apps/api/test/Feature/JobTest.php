<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    // ─── Public listing (FR-003, FR-004) ─────────────────────────────────────

    public function test_public_can_list_active_jobs(): void
    {
        JobPosting::factory()->count(3)->active()->create();
        JobPosting::factory()->count(2)->closed()->create();
        JobPosting::factory()->count(1)->draft()->create();

        $response = $this->getJson('/api/jobs');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'location', 'deadline', 'status']], 'meta']);
    }

    public function test_public_cannot_see_closed_or_draft_jobs(): void
    {
        JobPosting::factory()->closed()->create(['title' => 'Closed Job']);
        JobPosting::factory()->draft()->create(['title' => 'Draft Job']);
        JobPosting::factory()->active()->create(['title' => 'Active Job']);

        $response = $this->getJson('/api/jobs');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('Active Job', $response->json('data.0.title'));
    }

    public function test_search_filters_active_jobs_by_title(): void
    {
        JobPosting::factory()->active()->create(['title' => 'Backend Engineer']);
        JobPosting::factory()->active()->create(['title' => 'Frontend Developer']);
        JobPosting::factory()->active()->create(['title' => 'Product Manager']);

        $response = $this->getJson('/api/jobs?search=backend');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('Backend Engineer', $response->json('data.0.title'));
    }

    public function test_search_does_not_reveal_non_active_jobs(): void
    {
        JobPosting::factory()->closed()->create(['title' => 'Backend Closed']);
        JobPosting::factory()->active()->create(['title' => 'Backend Open']);

        $response = $this->getJson('/api/jobs?search=backend');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    // ─── Job detail (FR-005) ─────────────────────────────────────────────────

    public function test_public_can_view_active_job_detail(): void
    {
        $job = JobPosting::factory()->active()->create();

        $this->getJson("/api/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonStructure(['id', 'title', 'description', 'qualifications', 'location', 'deadline']);
    }

    public function test_public_cannot_view_closed_job_detail(): void
    {
        $job = JobPosting::factory()->closed()->create();

        $this->getJson("/api/jobs/{$job->id}")->assertNotFound();
    }

    // ─── HR CRUD (FR-006) ─────────────────────────────────────────────────────

    public function test_hr_can_create_job_posting(): void
    {
        $hr = User::factory()->hrAdmin()->create();

        $response = $this->actingAs($hr)->postJson('/api/jobs', [
            'title' => 'Senior PHP Developer',
            'description' => 'We are looking for a Laravel expert.',
            'qualifications' => 'PHP, Laravel, PostgreSQL',
            'location' => 'Jakarta',
            'deadline' => now()->addMonth()->format('Y-m-d'),
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Senior PHP Developer')
            ->assertJsonPath('status', 'active');

        $this->assertDatabaseHas('job_postings', ['title' => 'Senior PHP Developer']);
    }

    public function test_applicant_cannot_create_job_posting(): void
    {
        $applicant = User::factory()->applicant()->create();

        $this->actingAs($applicant)->postJson('/api/jobs', [
            'title' => 'Any Job',
            'description' => 'Test',
            'qualifications' => 'Test',
            'location' => 'Jakarta',
            'deadline' => now()->addMonth()->format('Y-m-d'),
        ])->assertForbidden();
    }

    public function test_unauthenticated_cannot_create_job_posting(): void
    {
        $this->postJson('/api/jobs', [
            'title' => 'Any Job',
            'description' => 'Test',
            'qualifications' => 'Test',
            'location' => 'Jakarta',
            'deadline' => now()->addMonth()->format('Y-m-d'),
        ])->assertUnauthorized();
    }

    public function test_hr_can_update_job_posting(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $job = JobPosting::factory()->create(['created_by' => $hr->id]);

        $this->actingAs($hr)->putJson("/api/jobs/{$job->id}", [
            'title' => 'Updated Title',
            'description' => $job->description,
            'qualifications' => $job->qualifications,
            'location' => $job->location,
            'deadline' => now()->addMonth()->format('Y-m-d'),
        ])->assertOk()->assertJsonPath('title', 'Updated Title');
    }

    public function test_hr_can_change_job_status(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $job = JobPosting::factory()->active()->create(['created_by' => $hr->id]);

        $this->actingAs($hr)
            ->patchJson("/api/jobs/{$job->id}/status", ['status' => 'closed'])
            ->assertOk()
            ->assertJsonPath('status', 'closed');

        $this->assertEquals('closed', $job->fresh()->status);
    }

    public function test_hr_can_soft_delete_job_posting(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $job = JobPosting::factory()->create(['created_by' => $hr->id]);

        $this->actingAs($hr)
            ->deleteJson("/api/jobs/{$job->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('job_postings', ['id' => $job->id]);
    }

    public function test_applicant_cannot_delete_job_posting(): void
    {
        $applicant = User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();

        $this->actingAs($applicant)
            ->deleteJson("/api/jobs/{$job->id}")
            ->assertForbidden();
    }

    public function test_create_job_requires_all_mandatory_fields(): void
    {
        $hr = User::factory()->hrAdmin()->create();

        $this->actingAs($hr)->postJson('/api/jobs', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['title', 'description', 'qualifications', 'location', 'deadline']]);
    }
}
