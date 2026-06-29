<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ChatThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatThread>
 */
class ChatThreadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'created_at' => now(),
        ];
    }
}
