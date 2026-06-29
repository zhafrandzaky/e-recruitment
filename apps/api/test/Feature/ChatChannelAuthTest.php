<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * WebSocket private-channel authorization for chat (FR-017, Task 2).
 *
 * This is an INDEPENDENT security boundary from the REST endpoints
 * (ChatTest): a subscription is authorized at POST /api/broadcasting/auth,
 * which runs the `chat.{applicationId}` callback in routes/channels.php.
 *
 * The test suite's default broadcaster is `null`, whose auth() is a no-op and
 * would never run the authorization callback. We therefore switch to the
 * `pusher` driver (same PusherBroadcaster the `reverb` driver uses, same base
 * Broadcaster::verifyUserCanAccessChannel path) with dummy credentials — HMAC
 * signing of an authorized response is local (no network, no live Reverb).
 *
 * A denied callback throws AccessDeniedHttpException → HTTP 403 (framework
 * behavior, uniform for both not-owned and not-found, so it does not leak
 * existence — see docs/DECISIONS.md ADR for this Phase).
 */
class ChatChannelAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'driver' => 'pusher',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => 'test-app-id',
                'options' => ['cluster' => 'mt1', 'useTLS' => false],
            ],
        ]);

        // Broadcast::channel() registers onto whichever driver is default at
        // registration time — at boot that was the test-default `null` driver.
        // Re-load the real routes/channels.php so the actual chat.{applicationId}
        // definition is registered on (and exercised against) the pusher driver.
        require base_path('routes/channels.php');
    }

    private function makeApplication(?User $applicant = null): Application
    {
        $applicant ??= User::factory()->applicant()->create();
        $job = JobPosting::factory()->active()->create();

        return Application::factory()->create([
            'job_posting_id' => $job->id,
            'applicant_id' => $applicant->id,
        ]);
    }

    private function authorize(string $applicationId): TestResponse
    {
        return $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-chat.'.$applicationId,
        ]);
    }

    public function test_owner_applicant_can_authorize_own_chat_channel(): void
    {
        $applicant = User::factory()->applicant()->create();
        $application = $this->makeApplication($applicant);

        // Sanctum::actingAs authenticates via the sanctum guard the channel uses
        // (the production SPA sends a bearer token to /api/broadcasting/auth).
        Sanctum::actingAs($applicant);

        $this->authorize($application->id)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_hr_can_authorize_any_chat_channel(): void
    {
        $hr = User::factory()->hrAdmin()->create();
        $application = $this->makeApplication();

        Sanctum::actingAs($hr);

        $this->authorize($application->id)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_other_applicant_cannot_authorize_foreign_chat_channel(): void
    {
        $owner = User::factory()->applicant()->create();
        $intruder = User::factory()->applicant()->create();
        $application = $this->makeApplication($owner);

        Sanctum::actingAs($intruder);

        // The critical rejection test: a different applicant must not be able to
        // subscribe to someone else's application chat channel.
        $this->authorize($application->id)->assertForbidden();
    }

    public function test_authorization_denied_for_nonexistent_application(): void
    {
        $applicant = User::factory()->applicant()->create();

        Sanctum::actingAs($applicant);

        // Uniform 403 (not 404) — same response as not-owned, so existence is
        // not leaked through the channel-auth boundary.
        $this->authorize((string) Str::uuid())->assertForbidden();
    }

    public function test_unauthenticated_cannot_authorize_channel(): void
    {
        $application = $this->makeApplication();

        $this->authorize($application->id)->assertUnauthorized();
    }
}
