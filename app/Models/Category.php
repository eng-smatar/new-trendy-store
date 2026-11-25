<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Category extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /** Last segment of Mart's "a > b > c" path. */
    public function label(): string
    {
        return trim(last(explode('>', $this->name)));
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
