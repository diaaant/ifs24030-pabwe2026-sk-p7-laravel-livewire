<?php

namespace Database\Factories;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(0, 100);
        $minStock = 5;

        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'sku' => strtoupper(fake()->bothify('SKU-####-??')),
            'description' => "## ".fake()->sentence(3)."\n\n- ".fake()->sentence()."\n- ".fake()->sentence(),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['pcs', 'box', 'kg', 'liter', 'pack']),
            'price' => fake()->randomFloat(2, 1000, 500000),
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock),
            'cover_image_path' => null,
        ];
    }
}
