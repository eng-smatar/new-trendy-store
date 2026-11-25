<?php

namespace App\Models;

use App\Services\ArabicText;
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
     * Normalised Arabic names of each category and its ancestors, keyed by id. Built at search time,
     * so an admin rename applies at once.
     *
     * @return Collection<int, string>
     */
    public static function arabicSearchIndex(): Collection
    {
        $all = static::all()->keyBy('name');

        return $all->mapWithKeys(function (self $category) use ($all) {
            $parts = explode(' > ', $category->name);
            $labels = [];

            foreach (array_keys($parts) as $i) {
                $path = implode(' > ', array_slice($parts, 0, $i + 1));
                $labels[] = ($all->get($path) ?? new self(['name' => $path]))->label();
            }

            return [$category->id => ArabicText::normalize(implode(' ', $labels))];
        });
    }

    /** Categories that have no ancestor in the list. */
    public static function roots(): Collection
    {
        $all = static::orderBy('name')->get();
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

    /** This category's id and every subcategory id. */
    public function branchIds(): Collection
    {
        return static::where('id', $this->id)->orWhere('name', 'like', $this->name.' > %')->pluck('id');
    }
}
