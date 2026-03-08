<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApiUser(): User
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test')->plainTextToken);
        return $user;
    }

    public function test_returns_only_users_organizations(): void
    {
        $user = $this->actingAsApiUser();
        $myOrg = Organization::factory()->create(['created_by' => $user->id]);
        $myOrg->members()->attach($user->id, ['role' => 'owner']);
        Organization::factory()->create(); // another org user doesn't belong to

        $this->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $myOrg->id)
            ->assertJsonPath('data.0.role', 'owner');
    }

    public function test_show_returns_org_with_credentials_count(): void
    {
        $user = $this->actingAsApiUser();
        $org = Organization::factory()->create(['created_by' => $user->id]);
        $org->members()->attach($user->id, ['role' => 'owner']);

        $this->getJson("/api/organizations/{$org->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $org->id)
            ->assertJsonPath('data.credentials_count', 0)
            ->assertJsonStructure(['data' => ['id', 'name', 'website_url', 'role', 'credentials_count']]);
    }

    public function test_cannot_view_org_user_is_not_member_of(): void
    {
        $this->actingAsApiUser();
        $otherOrg = Organization::factory()->create();

        $this->getJson("/api/organizations/{$otherOrg->id}")
            ->assertForbidden();
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/organizations')->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_organization(): void
    {
        $user = $this->actingAsApiUser();

        $this->postJson('/api/organizations', [
            'name'        => 'Acme Corp',
            'website_url' => 'https://acme.com',
            'description' => 'Test org',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Corp')
        ->assertJsonPath('data.role', 'owner')
        ->assertJsonPath('data.credentials_count', 0);

        $this->assertDatabaseHas('organizations', ['name' => 'Acme Corp', 'created_by' => $user->id]);
    }

    public function test_create_organization_requires_name(): void
    {
        $this->actingAsApiUser();

        $this->postJson('/api/organizations', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
