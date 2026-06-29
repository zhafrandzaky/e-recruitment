<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{job_title: string, scheduled_at: string, meeting_link: string, is_reschedule: bool}  $data
     */
    public function __construct(
        public readonly array $data,
    ) {
        $this->onConnection('redis');
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $jobTitle = $this->data['job_title'] ?? 'Lowongan';
        $appName = config('app.name', 'e-recruitment');
        $isReschedule = $this->data['is_reschedule'] ?? false;

        $actionLabel = $isReschedule ? 'Interview Dijadwalkan Ulang' : 'Interview Dijadwalkan';
        $subject = "{$actionLabel} — {$jobTitle}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo, ' . $notifiable->name . '!');

        if ($isReschedule) {
            $message->line("Jadwal interview Anda untuk posisi **{$jobTitle}** telah diubah.");
        } else {
            $message->line("Selamat! Anda telah dijadwalkan untuk interview posisi **{$jobTitle}**.");
        }

        return $message
            ->line("**Tanggal & Waktu:** {$this->data['scheduled_at']}")
            ->line("**Link Meeting:** [{$this->data['meeting_link']}]({$this->data['meeting_link']})")
            ->line('Interview akan dilaksanakan di platform meeting eksternal pada link di atas. ')
            ->line('Silakan klik link tersebut pada waktu yang telah ditentukan.')
            ->salutation("Salam,<br>{$appName}");
    }
}
