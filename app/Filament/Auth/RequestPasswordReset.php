<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    // Same answer whether or not the address exists, so the form cannot be used to find admins.
    protected function getFailureNotification(string $status): ?Notification
    {
        return $this->getSentNotification(Password::RESET_LINK_SENT);
    }
}
