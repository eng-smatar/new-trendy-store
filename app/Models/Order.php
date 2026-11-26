<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'products_total' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'customer_total' => 'decimal:2',
            'mart_total' => 'decimal:2',
            'expected_net' => 'decimal:2',
            'net_earning' => 'decimal:2',
            'free_shipping' => 'boolean',
            'quote_verified' => 'boolean',
            'request_body' => 'array',
            'next_attempt_at' => 'datetime',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function newReference(): string
    {
        return 'TW-'.Str::upper(Str::random(12));
    }

    public function fullAddress(): string
    {
        return implode('، ', array_filter([$this->address_line1, $this->town_name, $this->district_name]));
    }

    /** Warnings the owner still has to act on. */
    public const ATTENTION = ['total_mismatch', 'conflict', 'found_in_mart'];

    public function scopeNeedsAction($query): void
    {
        $query->where('status', OrderStatus::PendingReview)
            ->orWhere('status', OrderStatus::SendFailed)
            ->orWhereIn('attention_reason', self::ATTENTION)
            // Approved but still unconfirmed after several tries.
            ->orWhere(fn ($q) => $q->where('status', OrderStatus::Approved)->where('send_attempts', '>=', 5));
    }

    /** Record what Mart says about this order (create response or single read). */
    public function applyMart(array $mart): void
    {
        $cancelled = ! empty($mart['cancellation']['cancelled_at']);

        $this->status = $cancelled ? OrderStatus::Cancelled : OrderStatus::Submitted;
        $this->submitted_at ??= now();
        $this->next_attempt_at = null;
        $this->last_error = null;

        // Reads carry {id, name} with an Arabic name; the create response has only an English word.
        if (is_array($mart['status'] ?? null)) {
            $this->mart_status_id = $mart['status']['id'] ?? null;
            $this->mart_status_name = $mart['status']['name'] ?? null;
        }

        $this->earning_state = $mart['money']['earning_state'] ?? $this->earning_state;
        $this->net_earning = $mart['money']['net'] ?? $mart['reward']['net_reward'] ?? $this->net_earning;

        if ($cancelled) {
            $this->attention_reason = null; // nothing left to act on
        } elseif (isset($mart['cod_total'])) {
            $total = (float) $mart['cod_total'];
            $changed = $this->mart_total === null || abs($total - (float) $this->mart_total) >= 0.01;

            // Warn once per new figure: "handled" stays quiet until Mart changes the total again.
            if ($changed && abs($total - (float) $this->customer_total) >= 0.01) {
                $this->attention_reason = 'total_mismatch';
            }
            $this->mart_total = $total;
        }

        $this->save();
    }
}
