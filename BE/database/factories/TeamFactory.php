<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => fake()->unique()->words(2, true),
            'category' => fake()->randomElement(['U-10', 'U-12', 'U-15', 'Senior']),
        ];
    }
}
