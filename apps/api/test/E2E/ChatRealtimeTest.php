<?php

namespace Tests\E2E;

use App\Events\MessageSent;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * End-to-end server path for real-time chat (docs/TESTING.md Section 4).
 *
 * Verifies the full flow that lets "a message sent by one party appear for the
 * other": the message is persisted and broadcast on the shared per-application
 * private channel, and both participants read the same thread. The browser side
 * (DOM updates without a page reload) is covered by the frontend e2e test.
 */
class ChatRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private User $applicant;

    private User $hr;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicant = User::factory()->applicant()->create(['name' => 'Pelamar Satu']);
        $this->hr = User::factory()->hrAdmin()->create(['name' => 'Tim HR']);
        $job = JobPosting::factory()->active()->create();
        $this->application = Application::factory()->create([
            'job_posting_id' => $job->id,
            'applicant_id' => $this->applicant->id,
        ]);
    }

    public function test_message_from_hr_is_persisted_and_broadcast_on_the_shared_channel(): void
    {
        Event::fake([MessageSent::class]);

        $this->actingAs($this->hr)
            ->postJson("/api/applications/{$this->application->id}/messages", [
                'content' => 'Halo, ada pertanyaan?',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->hr->id,
            'content' => 'Halo, ada pertanyaan?',
        ]);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            $payload = $event->broadcastWith();

            return $event->broadcastOn()[0]->name === 'private-chat.'.$this->application->id
                && $payload['content'] === 'Halo, ada pertanyaan?'
                && $payload['sender_id'] === $this->hr->id;
        });
    }

    public function test_both_parties_exchange_messages_on_one_shared_thread(): void
    {
        // HR sends, then applicant replies.
        $this->actingAs($this->hr)
            ->postJson("/api/applications/{$this->application->id}/messages", ['content' => 'Pesan dari HR'])
            ->assertCreated();

        $this->actingAs($this->applicant)
            ->postJson("/api/applications/{$this->application->id}/messages", ['content' => 'Balasan pelamar'])
            ->assertCreated();

        // Exactly one thread; both parties see both messages in order.
        $this->assertDatabaseCount('chat_threads', 1);

        foreach ([$this->hr, $this->applicant] as $user) {
            $this->actingAs($user)
                ->getJson("/api/applications/{$this->application->id}/messages")
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('data.0.content', 'Pesan dari HR')
                ->assertJsonPath('data.1.content', 'Balasan pelamar');
        }
    }
}
