<?php

namespace Database\Factories;

use App\Services\ArabicText;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 1_000_000),
            'name' => 'منتج '.fake()->unique()->numberBetween(1, 1_000_000),
            'cost_price' => 100,
            'total_quantity' => 5,
            'mart_active' => true,
            'hidden' => false,
            'search_text' => fn (array $a) => ArabicText::normalize($a['name']),
        ];
    }
}
