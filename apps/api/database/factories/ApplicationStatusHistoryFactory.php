<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStatusHistory>
 */
class ApplicationStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'previous_status' => 'pending',
            'new_status' => 'shortlisted',
            'changed_by' => User::factory()->hrAdmin(),
            'changed_at' => now(),
        ];
    }
}
