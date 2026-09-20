<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'sport_type' => fake()->randomElement(['sepak bola', 'bulu tangkis', 'renang', 'bela diri']),
            'logo_url' => null,
            'subscription_plan' => SubscriptionPlan::Free,
            'subscription_status' => SubscriptionStatus::Active,
        ];
    }

    public function pro(): static
    {
        return $this->state(['subscription_plan' => SubscriptionPlan::Pro]);
    }
}
