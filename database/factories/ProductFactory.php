<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt    = fake()->dateTimeBetween('-2 years', 'now');
        $initialPrice = fake()->numberBetween(10_000, 200_000);
        $price        = $initialPrice + fake()->numberBetween(1_000, 50_000);

        return [
            'name'          => sprintf('Product %05d', fake()->unique()->numberBetween(1, 1_000_000)),
            'barcode'       => 'BR' . fake()->unique()->numerify('##########'),
            'initial_price' => $initialPrice,
            'price'         => $price,
            'category_id'   => null,
            'color_id'      => null,
            'type'          => 'package',
            'created_at'    => $createdAt,
            'updated_at'    => $createdAt,
        ];
    }
}
