<?php

namespace Tests\Feature\Api;

use App\Models\Credential;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialApiTest extends TestCase
{
    use RefreshDatabase;

    private function setupOwner(): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['created_by' => $user->id]);
        $org->members()->attach($user->id, ['role' => 'owner']);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token);
        return [$user, $org, $token];
    }

    public function test_owner_can_list_credentials(): void
    {
        [, $org] = $this->setupOwner();
        Credential::factory()->count(3)->create(['organization_id' => $org->id]);

        $this->getJson("/api/organizations/{$org->id}/credentials")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_owner_can_create_credential(): void
    {
        [, $org] = $this->setupOwner();

        $this->postJson("/api/organizations/{$org->id}/credentials", [
            'service_type' => 'hosting',
            'name'         => 'cPanel',
            'email'        => 'admin@example.com',
            'password'     => 'secret',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'cPanel');

        $this->assertDatabaseHas('credentials', ['name' => 'cPanel']);
    }

    public function test_owner_can_update_credential(): void
    {
        [, $org] = $this->setupOwner();
        $cred = Credential::factory()->create(['organization_id' => $org->id]);

        $this->putJson("/api/organizations/{$org->id}/credentials/{$cred->id}", [
            'name'     => 'Updated Name',
            'password' => 'newpassword',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_owner_can_delete_credential(): void
    {
        [, $org] = $this->setupOwner();
        $cred = Credential::factory()->create(['organization_id' => $org->id]);

        $this->deleteJson("/api/organizations/{$org->id}/credentials/{$cred->id}")
            ->assertOk();

        $this->assertDatabaseMissing('credentials', ['id' => $cred->id]);
    }

    public function test_member_cannot_create_credential(): void
    {
        $owner = User::factory()->create();
        $org = Organization::factory()->create(['created_by' => $owner->id]);
        $org->members()->attach($owner->id, ['role' => 'owner']);

        $member = User::factory()->create();
        $org->members()->attach($member->id, ['role' => 'member']);
        $this->withToken($member->createToken('test')->plainTextToken);

        $this->postJson("/api/organizations/{$org->id}/credentials", [
            'service_type' => 'other',
            'name'         => 'X',
            'password'     => 'y',
        ])
        ->assertForbidden();
    }

    public function test_search_returns_credentials_matching_url(): void
    {
        [$user, $org] = $this->setupOwner();
        Credential::factory()->create([
            'organization_id' => $org->id,
            'website_url'     => 'https://cpanel.example.com',
            'name'            => 'cPanel',
        ]);

        $this->getJson('/api/credentials/search?url=cpanel.example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_does_not_return_credentials_from_other_orgs(): void
    {
        [$user, $org] = $this->setupOwner();

        // Credential in another org the user doesn't belong to
        $otherOrg = Organization::factory()->create();
        Credential::factory()->create([
            'organization_id' => $otherOrg->id,
            'website_url'     => 'https://secret.example.com',
            'name'            => 'Secret',
        ]);

        $this->getJson('/api/credentials/search?url=secret.example.com')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
