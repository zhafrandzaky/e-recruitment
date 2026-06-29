<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_endpoint_is_publicly_accessible_without_auth(): void
    {
        $this->getJson('/api/public/stats')->assertOk();
    }

    public function test_stats_returns_correct_active_jobs_count(): void
    {
        JobPosting::factory()->count(3)->active()->create();
        JobPosting::factory()->count(2)->closed()->create();
        JobPosting::factory()->count(1)->draft()->create();

        $response = $this->getJson('/api/public/stats');

        $response->assertOk()
            ->assertJsonPath('active_jobs', 3);
    }

    public function test_stats_returns_correct_registered_applicants_count(): void
    {
        User::factory()->count(4)->applicant()->create();
        User::factory()->count(2)->hrAdmin()->create();

        $response = $this->getJson('/api/public/stats');

        $response->assertOk()
            ->assertJsonPath('registered_applicants', 4);
    }

    public function test_stats_returns_zero_when_no_data(): void
    {
        $response = $this->getJson('/api/public/stats');

        $response->assertOk()
            ->assertExactJson([
                'active_jobs' => 0,
                'registered_applicants' => 0,
            ]);
    }

    public function test_stats_excludes_soft_deleted_job_postings(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $activeJob = JobPosting::factory()->active()->create(['created_by' => $hr->id]);
        $deletedJob = JobPosting::factory()->active()->create(['created_by' => $hr->id]);
        $deletedJob->delete(); // soft delete

        $response = $this->getJson('/api/public/stats');

        $response->assertOk()
            ->assertJsonPath('active_jobs', 1);
    }

    public function test_stats_response_has_expected_structure(): void
    {
        $response = $this->getJson('/api/public/stats');

        $response->assertOk()
            ->assertJsonStructure(['active_jobs', 'registered_applicants']);
    }
}
