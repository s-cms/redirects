<?php

namespace SmartCms\Redirects\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SmartCms\Redirects\Models\Redirect;

class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition()
    {
        return [
            'old_url' => '/' . $this->faker->unique()->slug(),
            'new_url' => '/' . $this->faker->slug(),
            'status_code' => $this->faker->randomElement([301, 302]),
            'hit_count' => $this->faker->numberBetween(0, 1000),
            'last_hit_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the redirect has never been hit
     */
    public function unvisited(): static
    {
        return $this->state(fn (array $attributes) => [
            'hit_count' => 0,
            'last_hit_at' => null,
        ]);
    }

    /**
     * Indicate that the redirect has been hit recently
     */
    public function recentlyHit(): static
    {
        return $this->state(fn (array $attributes) => [
            'hit_count' => $this->faker->numberBetween(100, 10000),
            'last_hit_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}
