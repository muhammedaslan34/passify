<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class OrganizationAndServiceTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_an_organization_from_the_volt_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('pages.organizations.create')
            ->set('name', 'Acme Corp')
            ->set('website_url', 'https://acme.test')
            ->set('description', 'Test organization')
            ->call('save');

        $organization = Organization::query()->where('name', 'Acme Corp')->first();

        $this->assertNotNull($organization);
        $this->assertSame('acme-corp', $organization->slug);

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('organizations.show', $organization, absolute: false));

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_super_admin_service_types_page_is_displayed(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($admin);

        Volt::test('pages.admin.service-types')
            ->assertSee('Add service type')
            ->assertSee('Hosting');
    }

    public function test_service_types_route_is_protected_by_super_admin_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.service-types');

        $this->assertNotNull($route);
        $this->assertContains('super.admin', $route->gatherMiddleware());
    }

    public function test_super_admin_can_create_update_and_delete_service_types(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.service-types')
            ->call('openCreateModal')
            ->set('slug', 'docs')
            ->set('name', 'Documentation')
            ->set('color', 'sky')
            ->set('sort_order', 8)
            ->set('is_active', true)
            ->call('saveServiceType');

        $component->assertHasNoErrors();

        $serviceType = ServiceType::query()->where('slug', 'docs')->first();

        $this->assertNotNull($serviceType);
        $this->assertSame('Documentation', $serviceType->name);

        $component
            ->call('openEditModal', $serviceType->id)
            ->set('name', 'Docs')
            ->set('color', 'indigo')
            ->set('sort_order', 9)
            ->set('is_active', false)
            ->call('saveServiceType')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_types', [
            'id' => $serviceType->id,
            'slug' => 'docs',
            'name' => 'Docs',
            'color' => 'indigo',
            'sort_order' => 9,
            'is_active' => false,
        ]);

        $component->call('deleteServiceType', $serviceType->id);

        $this->assertDatabaseMissing('service_types', [
            'id' => $serviceType->id,
        ]);
    }
}
