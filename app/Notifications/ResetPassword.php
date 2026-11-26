<?php

namespace App\Notifications;

use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use Illuminate\Support\Facades\URL;

/**
 * Filament's reset email with a link whose signature covers only the path and parameters, so it
 * still checks out when a proxy changes the scheme or host PHP sees.
 */
class ResetPassword extends FilamentResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return url(URL::signedRoute('filament.admin.auth.password-reset.reset', [
            'email' => $notifiable->getEmailForPasswordReset(),
            'token' => $this->token,
        ], absolute: false));
    }
}
