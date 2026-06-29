<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class ResetPasswordNotification extends ResetPassword
{
    protected function resetUrl(mixed $notifiable): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        return "{$frontendUrl}/reset-password?token={$this->token}&email=".urlencode($notifiable->getEmailForPasswordReset());
    }
}
