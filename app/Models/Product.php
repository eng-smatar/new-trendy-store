<?php

namespace App\Models;

use App\Services\Pricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'combinations' => 'array',
            'cost_price' => 'decimal:2',
            'price_override' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'mart_active' => 'boolean',
            'hidden' => 'boolean',
            'featured' => 'boolean',
            'removed_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product) {
            $product->selling_price = Pricing::selling((float) $product->cost_price, $product->price_override === null ? null : (float) $product->price_override);
        });
    }

    /**
     * Supplier HTML without scripts, and without style or class, which could lay text over the page.
     */
    public static function safeHtml(?string $html): string
    {
        // After sanitising, attributes are always double-quoted and quotes in text are encoded.
        return preg_replace('/\s(?:style|class)="[^"]*"/i', '', (string) str((string) $html)->sanitizeHtml());
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** Could be ordered from Mart and the owner has not hidden it. */
    public function scopeSellable(Builder $query): void
    {
        // A zero price (Mart lists some at zero cost) would give the product away.
        $query->where('mart_active', true)->where('hidden', false)->whereNull('removed_at')->where('selling_price', '>', 0);
    }

    /** What shoppers see: sellable and within the listing filters in settings. */
    public function scopeVisible(Builder $query): void
    {
        $query->sellable();

        if (($minProfit = (float) Setting::get('listing_min_profit')) > 0) {
            // CAST: bound decimals arrive as text, which SQLite would not compare as numbers.
            $query->whereRaw('ROUND(selling_price - cost_price, 2) >= CAST(? AS DECIMAL(10,2))', [$minProfit]);
        }

        if (($categoryIds = Category::listedIds()) !== null) {
            $query->whereIn('category_id', $categoryIds);
        }
    }

    public function isSellable(): bool
    {
        return static::sellable()->whereKey($this->getKey())->exists();
    }

    public function isVisible(): bool
    {
        return static::visible()->whereKey($this->getKey())->exists();
    }

    public function combination(?int $id): ?array
    {
        return collect($this->combinations)->firstWhere('id', $id);
    }

    /** Units available for the product, or for one of its options. */
    public function stock(?int $combinationId = null): int
    {
        if (! $this->combinations) {
            return $combinationId === null ? max(0, (int) $this->total_quantity) : 0;
        }

        return max(0, (int) ($this->combination($combinationId)['available_quantity'] ?? 0));
    }

    public function optionLabel(?int $combinationId): ?string
    {
        $attributes = $this->combination($combinationId)['attributes'] ?? null;

        return $attributes ? collect($attributes)->map(fn ($a) => trim(($a['group'] ?? '').': '.($a['name'] ?? ''), ': '))->implode('، ') : null;
    }
}
