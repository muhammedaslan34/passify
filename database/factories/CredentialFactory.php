<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'service_type'    => $this->faker->randomElement(['hosting', 'domain', 'email', 'database', 'social_media', 'analytics', 'other']),
            'name'            => $this->faker->company(),
            'website_url'     => $this->faker->url(),
            'email'           => $this->faker->email(),
            'password'        => $this->faker->password(),
            'note'            => $this->faker->sentence(),
        ];
    }
}
