<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Application;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * REST chat endpoints (FR-017, docs/API.md Section 6).
 *
 * Covers ownership enforcement on GET/POST (independent of the WebSocket
 * channel — see ChatChannelAuthTest), message persistence, lazy thread
 * creation, and that a message is broadcast even when no client is connected.
 */
class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function makeApplication(?User $applicant = null): Application
    {
        $applicant ??= User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();

        return Application::factory()->create([
            'job_posting_id' => $job->id,
            'applicant_id' => $applicant->id,
        ]);
    }

    // ─── Read history ────────────────────────────────────────────────────────

    public function test_owner_applicant_can_view_own_chat_history(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);
        $thread = ChatThread::factory()->create(['application_id' => $application->id]);
        ChatMessage::factory()->create([
            'chat_thread_id' => $thread->id,
            'sender_id' => $applicant->id,
            'content' => 'Halo, kapan jadwal interview?',
        ]);

        $response = $this->actingAs($applicant)
            ->getJson("/api/applications/{$application->id}/messages");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Halo, kapan jadwal interview?')
            ->assertJsonStructure(['data' => [['id', 'content', 'sender_id', 'sent_at', 'sender' => ['id', 'name']]]]);
    }

    public function test_history_returns_messages_in_chronological_order(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);
        $thread = ChatThread::factory()->create(['application_id' => $application->id]);

        ChatMessage::factory()->create([
            'chat_thread_id' => $thread->id,
            'sender_id' => $applicant->id,
            'content' => 'pertama',
            'sent_at' => now()->subMinutes(5),
        ]);
        ChatMessage::factory()->create([
            'chat_thread_id' => $thread->id,
            'sender_id' => $applicant->id,
            'content' => 'kedua',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($applicant)
            ->getJson("/api/applications/{$application->id}/messages");

        $response->assertOk()
            ->assertJsonPath('data.0.content', 'pertama')
            ->assertJsonPath('data.1.content', 'kedua');
    }

    public function test_hr_can_view_any_application_chat_history(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $application = $this->makeApplication();

        $this->actingAs($hr)
            ->getJson("/api/applications/{$application->id}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ─── Send ────────────────────────────────────────────────────────────────

    public function test_owner_applicant_can_send_message(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);

        $response = $this->actingAs($applicant)
            ->postJson("/api/applications/{$application->id}/messages", [
                'content' => 'Terima kasih atas kesempatannya.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('content', 'Terima kasih atas kesempatannya.')
            ->assertJsonPath('sender_id', $applicant->id)
            ->assertJsonStructure(['id', 'content', 'sender_id', 'sent_at', 'sender' => ['id', 'name']]);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $applicant->id,
            'content' => 'Terima kasih atas kesempatannya.',
        ]);
    }

    public function test_hr_can_send_message_for_any_application(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $application = $this->makeApplication();

        $this->actingAs($hr)
            ->postJson("/api/applications/{$application->id}/messages", [
                'content' => 'Selamat, Anda lolos ke tahap berikutnya.',
            ])
            ->assertCreated()
            ->assertJsonPath('sender_id', $hr->id);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $hr->id,
            'content' => 'Selamat, Anda lolos ke tahap berikutnya.',
        ]);
    }

    public function test_empty_content_is_rejected(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);

        $this->actingAs($applicant)
            ->postJson("/api/applications/{$application->id}/messages", ['content' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    public function test_overlong_content_is_rejected(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);

        $this->actingAs($applicant)
            ->postJson("/api/applications/{$application->id}/messages", [
                'content' => str_repeat('a', 5001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    // ─── Ownership enforcement (docs/SECURITY.md 3.2) ────────────────────────

    public function test_other_applicant_cannot_view_messages(): void
    {
        $owner = User::factory()->applicant()->create();
        $intruder = User::factory()->applicant()->create();
        $application = $this->makeApplication($owner);

        // 404, not 403 — do not leak existence of another applicant's application.
        $this->actingAs($intruder)
            ->getJson("/api/applications/{$application->id}/messages")
            ->assertNotFound();
    }

    public function test_other_applicant_cannot_send_message(): void
    {
        $owner = User::factory()->applicant()->create();
        $intruder = User::factory()->applicant()->create();
        $application = $this->makeApplication($owner);

        $this->actingAs($intruder)
            ->postJson("/api/applications/{$application->id}/messages", ['content' => 'sneaky'])
            ->assertNotFound();

        $this->assertDatabaseMissing('chat_messages', ['content' => 'sneaky']);
    }

    public function test_unauthenticated_cannot_access_chat(): void
    {
        $application = $this->makeApplication();

        $this->getJson("/api/applications/{$application->id}/messages")->assertUnauthorized();
        $this->postJson("/api/applications/{$application->id}/messages", ['content' => 'hi'])->assertUnauthorized();
    }

    // ─── Persistence + broadcast, lazy thread ────────────────────────────────

    public function test_message_is_persisted_and_broadcast_even_with_no_subscriber(): void
    {
        Event::fake([MessageSent::class]);

        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);

        $this->actingAs($applicant)
            ->postJson("/api/applications/{$application->id}/messages", ['content' => 'tetap tersimpan'])
            ->assertCreated();

        // Persisted regardless of whether any WebSocket client was connected.
        $this->assertDatabaseHas('chat_messages', ['content' => 'tetap tersimpan']);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($application) {
            return $event->applicationId === $application->id
                && $event->message->content === 'tetap tersimpan'
                && $event->broadcastOn()[0]->name === 'private-chat.'.$application->id;
        });
    }

    public function test_thread_is_created_lazily_and_reused(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);

        // No thread exists until first use.
        $this->assertDatabaseCount('chat_threads', 0);

        $this->actingAs($applicant)
            ->postJson("/api/applications/{$application->id}/messages", ['content' => 'satu'])
            ->assertCreated();
        $this->actingAs($applicant)
            ->postJson("/api/applications/{$application->id}/messages", ['content' => 'dua'])
            ->assertCreated();

        // Exactly one thread for the application, shared by both messages.
        $this->assertDatabaseCount('chat_threads', 1);
        $thread = ChatThread::where('application_id', $application->id)->firstOrFail();
        $this->assertEquals(2, $thread->messages()->count());
    }

    public function test_reading_history_lazily_creates_thread(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);

        $this->assertDatabaseCount('chat_threads', 0);

        $this->actingAs($applicant)
            ->getJson("/api/applications/{$application->id}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount('chat_threads', 1);
    }
}
