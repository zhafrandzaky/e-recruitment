<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{job_title: string}  $data
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

        return (new MailMessage)
            ->subject("Interview Dibatalkan — {$jobTitle}")
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Interview Anda untuk posisi **{$jobTitle}** telah dibatalkan oleh HR.")
            ->line('Jika Anda memiliki pertanyaan, silakan hubungi tim HR melalui fitur chat pada halaman lamaran Anda.')
            ->salutation("Salam,<br>{$appName}");
    }
}
