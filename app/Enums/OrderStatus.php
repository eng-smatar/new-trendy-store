<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingReview = 'pending_review';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Submitted = 'submitted';
    case SendFailed = 'send_failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('orders.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingReview => 'warning',
            self::Approved => 'info',
            self::Submitted => 'success',
            self::SendFailed => 'danger',
            self::Rejected, self::Cancelled => 'gray',
        };
    }
}
