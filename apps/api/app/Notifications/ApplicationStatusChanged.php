<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{job_title: string, previous_status: string, new_status: string}  $data
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

        $statusLabels = [
            'pending' => 'Menunggu',
            'shortlisted' => 'Lolos Seleksi Berkas',
            'rejected' => 'Ditolak',
        ];
        $newLabel = $statusLabels[$this->data['new_status']] ?? $this->data['new_status'];

        $message = (new MailMessage)
            ->subject("Status Lamaran Diperbarui — {$jobTitle}")
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Status lamaran Anda untuk posisi **{$jobTitle}** telah diperbarui.");

        if ($this->data['new_status'] === 'shortlisted') {
            $message->line("Selamat! Status Anda sekarang: **{$newLabel}**.")
                ->line('Tim HR akan menghubungi Anda untuk penjadwalan interview.');
        } elseif ($this->data['new_status'] === 'rejected') {
            $message->line("Status Anda sekarang: **{$newLabel}**.")
                ->line('Terima kasih atas minat Anda. Jangan berkecil hati — tetap pantau lowongan kami yang lain.');
        } else {
            $message->line("Status Anda sekarang: **{$newLabel}**.");
        }

        return $message->salutation("Salam,<br>{$appName}");
    }
}
