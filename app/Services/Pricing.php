<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class Pricing
{
    public static function selling(float $cost, ?float $override = null, ?float $markupPercent = null): float
    {
        $markupPercent ??= (float) Setting::get('markup_percent');

        // An override the cost has overtaken would earn nothing: fall back to the markup.
        return $override !== null && $override >= $cost
            ? $override
            : max($cost, round($cost * (1 + max(0, $markupPercent) / 100), 2));
    }

    /** Re-price the whole catalogue after a markup change. */
    public static function recalculate(): void
    {
        Setting::flush(); // another process may have saved a new markup during a long sync
        $factor = 1 + max(0, (float) Setting::get('markup_percent')) / 100;

        DB::table('products')->update(['selling_price' => DB::raw(sprintf(
            'CASE WHEN price_override IS NULL OR price_override < cost_price THEN ROUND(cost_price * %.6F, 2) ELSE price_override END',
            $factor,
        ))]);
    }
}
