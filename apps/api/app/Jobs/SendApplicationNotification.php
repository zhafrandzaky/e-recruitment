<?php

namespace App\Jobs;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder notification job for application status changes.
 *
 * This job is queued whenever an application is submitted or its status changes.
 * Phase 3 will replace this placeholder with actual email sending via Resend.
 *
 * The call site exists and is documented so Phase 3 knows exactly where to wire
 * in the real notification logic — no hunting through the codebase required.
 *
 * Current behavior: logs the event at info level for development visibility.
 * This is intentionally a low-impact placeholder, not a stub that silently
 * drops notifications.
 */
class SendApplicationNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  Application  $application  The application record
     * @param  string  $event  One of: 'submitted', 'status_changed'
     * @param  array|null  $context  Additional context (e.g. old/new status for status_changed events)
     */
    public function __construct(
        public readonly Application $application,
        public readonly string $event,
        public readonly ?array $context = null,
    ) {}

    public function handle(): void
    {
        // Placeholder: Phase 3 will implement actual email sending via Resend.
        // The call site exists at ApplicationController::submit() and ::updateStatus().
        Log::info('Notification queued (placeholder — Phase 3 will send real email)', [
            'event' => $this->event,
            'application_id' => $this->application->id,
            'applicant_email' => $this->application->applicant->email,
            'context' => $this->context,
        ]);
    }
}
