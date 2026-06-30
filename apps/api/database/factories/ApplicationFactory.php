<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'applicant_id' => User::factory()->applicant(),
            'cv_path' => 'applications/cv/'.fake()->uuid().'.pdf',
            'cv_original_filename' => fake()->words(3, true).'.pdf',
            'additional_data' => [
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
            ],
            'status' => 'pending',
            'applied_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function shortlisted(): static
    {
        return $this->state(['status' => 'shortlisted']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }

    public function hired(): static
    {
        return $this->state(['status' => 'hired']);
    }
}
