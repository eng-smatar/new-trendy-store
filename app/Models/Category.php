<?php

namespace App\Models;

use App\Services\ArabicText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Category extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /** The admin's Arabic name, else the built-in one, else Mart's English. */
    public function label(): string
    {
        return $this->name_ar ?: $this->defaultLabel();
    }

    /** Built-in Arabic name for the last segment of Mart's "a > b > c" path. */
    public function defaultLabel(): string
    {
        $segment = trim(last(explode('>', $this->name)));

        return trans('categories', [], 'ar')[$segment] ?? $segment;
    }

    /**
     * Arabic labels along each category's path, keyed by id.
     *
     * @return Collection<int, array<int, string>>
     */
    public static function pathLabels(): Collection
    {
        $all = static::orderBy('name')->get()->keyBy('name');

        return $all->mapWithKeys(function (self $category) use ($all) {
            $parts = explode(' > ', $category->name);

            return [$category->id => array_map(function (int $i) use ($parts, $all) {
                $path = implode(' > ', array_slice($parts, 0, $i + 1));

                return ($all->get($path) ?? new self(['name' => $path]))->label();
            }, array_keys($parts))];
        });
    }

    /** Built at search time, so an admin rename applies at once. */
    public static function arabicSearchIndex(): Collection
    {
        return static::pathLabels()->map(fn (array $labels) => ArabicText::normalize(implode(' ', $labels)));
    }

    /**
     * Ids the admin chose to list, with their subcategories. Null means every category.
     *
     * @return Collection<int, int>|null
     */
    public static function listedIds(): ?Collection
    {
        return app('store.listed_category_ids')['ids'];
    }

    public static function chosenBranchIds(): ?Collection
    {
        $chosen = array_filter((array) Setting::get('listing_categories'));

        if (! $chosen) {
            return null;
        }

        $all = static::all(['id', 'name']);

        return $all->whereIn('id', $chosen)->flatMap(fn (self $category) => $category->branchIds($all))->unique()->values();
    }

    public function scopeListed(Builder $query): void
    {
        if (($ids = static::listedIds()) !== null) {
            $query->whereIn('id', $ids);
        }
    }

    /** Listed categories that have no listed ancestor. */
    public static function roots(): Collection
    {
        $all = static::listed()->orderBy('name')->get();
        $names = $all->pluck('name')->flip();

        return $all->reject(function (self $category) use ($names) {
            $parts = explode(' > ', $category->name);
            while (array_pop($parts) !== null && $parts) {
                if ($names->has(implode(' > ', $parts))) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /** This category's id and every subcategory id. Pass $all to avoid loading it again. */
    public function branchIds(?Collection $all = null): Collection
    {
        return ($all ?? static::all(['id', 'name']))
            ->filter(fn (self $c) => $c->id === $this->id || str_starts_with($c->name, $this->name.' > '))
            ->pluck('id')->values();
    }
}
