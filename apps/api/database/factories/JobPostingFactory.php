<?php

namespace Database\Factories;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(3, true),
            'qualifications' => fake()->paragraphs(2, true),
            'location' => fake()->city().', Indonesia',
            'deadline' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'status' => 'active',
            'created_by' => User::factory()->hrAdmin(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }

    public function expired(): static
    {
        return $this->state([
            'deadline' => fake()->dateTimeBetween('-1 month', '-1 day')->format('Y-m-d'),
            'status' => 'active',
        ]);
    }
}
