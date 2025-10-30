<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone_number' => '+'.fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'metadata' => [
                'source' => 'order_webhook',
                'wordpress_user_id' => fake()->numberBetween(1000, 99999),
            ],
        ];
    }
}
