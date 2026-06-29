<?php

namespace App\Jobs;

use App\Models\Application;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job that sends email notifications for application events.
 *
 * Dispatched whenever an application is submitted or its status changes.
 * All sending is queued (Redis-backed) — never synchronous in the request lifecycle.
 * Send failures are logged for retry via Laravel's failed job handling.
 */
class SendApplicationNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * @param  Application  $application  The application record
     * @param  string  $event  One of: 'submitted', 'status_changed'
     * @param  array|null  $context  Additional context (e.g. old/new status for status_changed events)
     */
    public function __construct(
        public readonly Application $application,
        public readonly string $event,
        public readonly ?array $context = null,
    ) {
        $this->onConnection('redis');
    }

    public function handle(): void
    {
        $applicant = $this->application->applicant;

        match ($this->event) {
            'submitted' => $applicant->notify(new ApplicationSubmitted($this->application)),
            'status_changed' => $applicant->notify(new ApplicationStatusChanged([
                'job_title' => $this->application->jobPosting?->title ?? 'Lowongan',
                'previous_status' => $this->context['previous_status'] ?? '',
                'new_status' => $this->context['new_status'] ?? $this->application->status,
            ])),
            default => Log::warning('Unknown notification event type', [
                'event' => $this->event,
                'application_id' => $this->application->id,
            ]),
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Notification job failed', [
            'event' => $this->event,
            'application_id' => $this->application->id,
            'applicant_email' => $this->application->applicant->email,
            'error' => $exception?->getMessage(),
        ]);
    }
}
