<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
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
            'full_name'  => fake()->name(),
            'phone'      => fake()->unique()->numerify('+998##-###-##-##'),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
