<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.store')]
class Home extends Component
{
    public function render()
    {
        return view('livewire.home', [
            'announcement' => Setting::get('announcement'),
            // A new random pick on every visit.
            'slides' => Product::visible()->where('total_quantity', '>', 0)->whereNotNull('cover_image')->inRandomOrder()->limit(5)->get(),
            'categories' => Category::roots(),
            'featured' => Product::visible()->where('featured', true)->orderByDesc('id')->limit(8)->get(),
            'arrivals' => Product::visible()->where('total_quantity', '>', 0)->orderByDesc('id')->limit(12)->get(),
        ]);
    }
}
