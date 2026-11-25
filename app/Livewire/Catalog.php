<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Services\ArabicText;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.store')]
class Catalog extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $categoryId = null;

    #[Url]
    public string $q = '';

    #[Url]
    public string $min = '';

    #[Url]
    public string $max = '';

    #[Url]
    public bool $available = false;

    #[Url]
    public string $sort = 'newest';

    public function mount(?int $id = null): void
    {
        $this->categoryId = $id === null ? null : Category::findOrFail($id)->id;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $category = $this->categoryId ? Category::find($this->categoryId) : null;
        $words = array_filter(explode(' ', ArabicText::normalize(mb_substr($this->q, 0, 100))), 'strlen');

        $products = Product::visible()
            ->when($category, fn ($query) => $query->whereIn('category_id', $category->branchIds()))
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->where('search_text', 'like', '%'.addcslashes($word, '%_\\').'%');
                }
            })
            ->when(is_numeric($this->min), fn ($query) => $query->where('selling_price', '>=', (float) $this->min))
            ->when(is_numeric($this->max), fn ($query) => $query->where('selling_price', '<=', (float) $this->max))
            ->when($this->available, fn ($query) => $query->where('total_quantity', '>', 0));

        match ($this->sort) {
            'price_asc' => $products->orderBy('selling_price'),
            'price_desc' => $products->orderByDesc('selling_price'),
            default => $products,
        };

        return view('livewire.catalog', [
            'category' => $category,
            'subcategories' => $category
                ? Category::where('parent_path', $category->name)->orderBy('name')->get()
                : collect(),
            'products' => $products->orderByDesc('id')->paginate(24),
        ])->title($category?->label() ?? __('store.search'));
    }
}
