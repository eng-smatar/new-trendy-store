<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Town extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['fees' => 'array', 'available' => 'boolean'];

    /** Delivery fee for a products total, or null when no bracket applies. */
    public function fee(float $subtotal): ?float
    {
        foreach ((array) $this->fees as $bracket) {
            if ($subtotal >= $bracket['from_total'] && ($bracket['to_total'] === null || $subtotal < $bracket['to_total'])) {
                return (float) $bracket['fee'];
            }
        }

        return null;
    }

    public function zone(): string
    {
        return match (true) {
            $this->district_id == 248 => 'Jerusalem',
            $this->district_id >= 256 && $this->district_id <= 262 => 'Isreal', // sic: key from SHIPPING_ADDRESS.md
            default => 'default',
        };
    }
}
