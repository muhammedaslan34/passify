<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'service_type_id' => ServiceType::query()->inRandomOrder()->value('id') ?? 1,
            'name'            => $this->faker->company(),
            'website_url'     => $this->faker->url(),
            'email'           => $this->faker->email(),
            'password'        => $this->faker->password(),
            'note'            => $this->faker->sentence(),
        ];
    }
}
