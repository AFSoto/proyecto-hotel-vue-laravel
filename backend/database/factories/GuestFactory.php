<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'document_type' => 'cc',
            'document_number' => (string) fake()->unique()->numerify('CC-########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('3#########'),
        ];
    }
}
