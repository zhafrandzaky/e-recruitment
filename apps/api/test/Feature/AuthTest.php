<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── Register ────────────────────────────────────────────────────────────

    public function test_applicant_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);

        $this->assertEquals('applicant', $response->json('user.role'));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'applicant']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable();
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'hr@example.com', 'role' => 'hr_admin']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'hr@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
        $this->assertEquals('hr_admin', $response->json('user.role'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $wrongPasswordResponse = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $wrongEmailResponse = $this->postJson('/api/auth/login', [
            'email' => 'doesnotexist@example.com',
            'password' => 'wrongpassword',
        ]);

        $wrongPasswordResponse->assertUnauthorized();
        $wrongEmailResponse->assertUnauthorized();

        // Generic combined message — both cases return same message (no enumeration)
        $this->assertEquals(
            $wrongPasswordResponse->json('error.message'),
            $wrongEmailResponse->json('error.message')
        );
    }

    // ─── Account Lockout (FR-001a) ────────────────────────────────────────────

    public function test_account_locks_after_three_consecutive_failures(): void
    {
        $user = User::factory()->create(['email' => 'target@example.com']);

        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'target@example.com',
                'password' => 'wrongpassword',
            ])->assertUnauthorized();
        }

        // Third attempt locks the account
        $response = $this->postJson('/api/auth/login', [
            'email' => 'target@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(423); // HTTP 423 Locked

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLocked());
    }

    public function test_locked_account_is_rejected_on_subsequent_attempts(): void
    {
        $user = User::factory()->locked()->create(['email' => 'locked@example.com']);

        // Even correct password is rejected while locked
        $response = $this->postJson('/api/auth/login', [
            'email' => 'locked@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(423)
            ->assertJsonPath('error.code', 'ACCOUNT_LOCKED');
    }

    public function test_failed_attempts_reset_after_successful_login(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        // Two failures
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'reset@example.com',
                'password' => 'wrong',
            ]);
        }

        // Successful login
        $this->postJson('/api/auth/login', [
            'email' => 'reset@example.com',
            'password' => 'password',
        ])->assertOk();

        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/auth/logout')->assertOk();

        // All tokens for the user should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    // ─── Forgot Password (FR-002) ─────────────────────────────────────────────

    public function test_forgot_password_always_returns_same_response_regardless_of_email(): void
    {
        User::factory()->create(['email' => 'real@example.com']);

        $responseForRealEmail = $this->postJson('/api/auth/forgot-password', [
            'email' => 'real@example.com',
        ]);

        $responseForFakeEmail = $this->postJson('/api/auth/forgot-password', [
            'email' => 'notregistered@example.com',
        ]);

        // Both must return 200 and identical structure — prevents email enumeration
        $responseForRealEmail->assertOk();
        $responseForFakeEmail->assertOk();
        $this->assertEquals(
            $responseForRealEmail->json('message'),
            $responseForFakeEmail->json('message')
        );
    }
}
