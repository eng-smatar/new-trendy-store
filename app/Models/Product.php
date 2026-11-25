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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('mart_active', true)->where('hidden', false)->whereNull('removed_at');
    }

    public function isVisible(): bool
    {
        return $this->mart_active && ! $this->hidden && $this->removed_at === null;
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
