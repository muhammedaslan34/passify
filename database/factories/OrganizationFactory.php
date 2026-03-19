<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\SlugService;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name'        => $name,
            'slug'        => SlugService::generateUnique($name),
            'website_url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
            'created_by'  => User::factory(),
        ];
    }
}
