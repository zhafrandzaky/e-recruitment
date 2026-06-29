<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Application $application,
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
        $jobTitle = $this->application->jobPosting?->title ?? 'Lowongan';
        $appName = config('app.name', 'e-recruitment');

        return (new MailMessage)
            ->subject("Lamaran Diterima — {$jobTitle}")
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Lamaran Anda untuk posisi **{$jobTitle}** telah berhasil kami terima.")
            ->line('Status lamaran Anda saat ini: **Menunggu** (Pending).')
            ->line('Kami akan meninjau lamaran Anda dan menghubungi Anda kembali jika ada perkembangan.')
            ->line('Anda dapat memantau status lamaran Anda melalui halaman "Lamaran Saya".')
            ->salutation("Salam,<br>{$appName}");
    }
}
