<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->company(),
            'website_url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
            'created_by'  => User::factory(),
        ];
    }
}
