<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed', 'resolved']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'assigned_to' => null, // Can be set to a user ID when creating
        ];
    }
}
