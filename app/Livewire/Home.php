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
            'banners' => (array) Setting::get('banners'),
            'categories' => Category::roots(),
            'featured' => Product::visible()->where('featured', true)->orderByDesc('id')->limit(8)->get(),
            'arrivals' => Product::visible()->where('total_quantity', '>', 0)->orderByDesc('id')->limit(12)->get(),
        ]);
    }
}
