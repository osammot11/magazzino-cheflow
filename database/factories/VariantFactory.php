<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'name' => 'Standard', 'sku' => strtoupper(fake()->unique()->bothify('SKU-????-####')), 'price_cents' => 2500, 'low_stock_threshold' => 5, 'quantity' => 0];
    }
}
