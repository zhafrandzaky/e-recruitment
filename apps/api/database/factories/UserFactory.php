<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'applicant',
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ];
    }

    public function hrAdmin(): static
    {
        return $this->state(['role' => 'hr_admin']);
    }

    public function applicant(): static
    {
        return $this->state(['role' => 'applicant']);
    }

    public function locked(): static
    {
        return $this->state([
            'failed_login_attempts' => 3,
            'locked_until' => now()->addMinutes(15),
        ]);
    }
}
