<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'             => fake()->words(3, true),
            'type'             => DiscountType::GlobalPercent,
            'percent'          => fake()->randomFloat(2, 1, 50),
            'min_order_amount' => null,
            'starts_at'        => null,
            'ends_at'          => null,
            'is_active'        => true,
        ];
    }
}
