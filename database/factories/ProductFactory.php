<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('??-####')),
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'description' => $this->faker->optional()->sentence(),
            'unit' => $this->faker->randomElement(['pcs', 'box', 'kg']),
            'cost_price' => $this->faker->randomFloat(2, 1, 200),
            'sale_price' => $this->faker->randomFloat(2, 1, 400),
            'stock_on_hand' => 0,
            'reorder_level' => $this->faker->numberBetween(0, 20),
            'location' => $this->faker->optional()->bothify('Shelf ?#'),
            'is_active' => true,
        ];
    }
}
