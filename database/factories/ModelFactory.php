<?php

namespace SmartCms\Redirects\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SmartCms\Redirects\Models\Redirect;

class ModelFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition()
    {
        return [
            'old_url' => $this->faker->slug(),
            'new_url' => $this->faker->slug(),
            'status_code' => $this->faker->randomElement([301, 302]),
        ];
    }
}
