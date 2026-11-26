<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.store')]
class Home extends Component
{
    /**
     * Top categories as picture tiles, the fullest first; the rest follow as plain links.
     *
     * @return array{tiles: Collection, more: Collection}
     */
    private function categoryTiles(array $shown): array
    {
        $shown = $shown ?: [0];
        $placeholders = implode(',', array_fill(0, count($shown), '?'));
        $perCategory = Product::visible()->whereNotNull('cover_image')->whereNotNull('category_id')
            // A tile's cover skips products already shown under "new arrivals".
            ->selectRaw('category_id, count(*) as products, max(case when id in ('.$placeholders.') then null else id end) as newest', $shown)
            ->groupBy('category_id')->get();
        $all = Category::all(['id', 'name']);

        $roots = Category::roots()->map(function (Category $root) use ($perCategory, $all) {
            $stats = $perCategory->whereIn('category_id', $root->branchIds($all));

            return ['category' => $root, 'products' => (int) $stats->sum('products'), 'newest' => $stats->max('newest')];
        })->sortByDesc('products')->values();

        $tiles = $roots->where('products', '>', 0)->whereNotNull('newest')->take(8);
        $covers = Product::findMany($tiles->pluck('newest'))->pluck('cover_image', 'id');

        return [
            'tiles' => $tiles->map(fn (array $tile) => $tile + ['image' => $covers[$tile['newest']] ?? null])->values(),
            'more' => $roots->pluck('category')->diff($tiles->pluck('category'))->values(),
        ];
    }

    /** Five random products, kept for the period set in settings (0 = new on every visit). */
    private function slides(): Collection
    {
        $buyable = fn () => Product::visible()->where('total_quantity', '>', 0)->whereNotNull('cover_image');
        $pick = fn () => $buyable()->inRandomOrder()->limit(5)->pluck('id')->all();
        $hours = (int) Setting::get('slider_cache_hours');
        $ids = $hours > 0 ? Cache::remember('home.slides', now()->addHours($hours), $pick) : $pick();

        // Only ids are cached, so price and availability are always current.
        $slides = $buyable()->findMany($ids);

        if ($slides->isEmpty()) {
            Cache::forget('home.slides');
            $slides = $buyable()->findMany($ids = $pick());
        }

        return $slides->sortBy(fn (Product $product) => array_search($product->id, $ids))->values();
    }

    public function render()
    {
        $arrivals = Product::visible()->where('total_quantity', '>', 0)->orderByDesc('id')->limit(12)->get();

        return view('livewire.home', [
            'announcement' => Setting::get('announcement'),
            'slides' => $this->slides(),
            'categories' => $this->categoryTiles($arrivals->modelKeys()),
            'featured' => Product::visible()->where('featured', true)->orderByDesc('id')->limit(8)->get(),
            'arrivals' => $arrivals,
        ]);
    }
}
