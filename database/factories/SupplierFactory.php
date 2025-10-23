<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'full_name'  => fake()->company(),
            'phone'      => fake()->unique()->numerify('+998##-###-##-##'),
            'address'    => fake()->address(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
