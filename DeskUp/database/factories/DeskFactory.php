<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Desk>
 */
class DeskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $deskNumber = $this->faker->unique()->numberBetween(1, 9999);

        return [
            'name' => 'DESK ' . $deskNumber,
            'desk_number' => $deskNumber, 
            'api_desk_id' => null,
            'position_x' => null,
            'position_y' => null,
            'is_active' => true,
            'user_id' => null,
        ];
    }
}
