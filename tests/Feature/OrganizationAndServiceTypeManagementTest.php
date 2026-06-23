<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyOrganizationMembership;
use App\Models\Organization;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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

    public function test_org_membership_middleware_resolves_slug_route_parameters_for_existing_organizations(): void
    {
        $user = User::factory()->create();
        $oldOrganization = Organization::factory()->create([
            'created_by' => $user->id,
            'name' => 'Old Org',
            'slug' => 'old-org',
        ]);
        $oldOrganization->members()->attach($user->id, ['role' => 'owner']);

        $newOrganization = Organization::factory()->create([
            'created_by' => $user->id,
            'name' => 'New Org',
            'slug' => 'new-org',
        ]);
        $newOrganization->members()->attach($user->id, ['role' => 'owner']);

        $request = Request::create('/organizations/old-org', 'GET');
        $route = new Route('GET', '/organizations/{organization}', fn () => response('ok'));
        $route->bind($request);

        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        $response = app(VerifyOrganizationMembership::class)->handle(
            $request,
            fn ($resolvedRequest) => response($resolvedRequest->route('organization')->name)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertInstanceOf(Organization::class, $request->route('organization'));
        $this->assertSame($oldOrganization->id, $request->route('organization')->id);
        $this->assertSame('Old Org', $response->getContent());
    }

    public function test_owner_can_delete_owned_organization_from_the_index_page(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['created_by' => $user->id]);
        $organization->members()->attach($user->id, ['role' => 'owner']);

        $this->actingAs($user);

        Volt::test('pages.organizations.index')
            ->call('deleteOrganization', $organization->id)
            ->assertHasNoErrors()
            ->assertSee('Organization deleted.');

        $this->assertDatabaseMissing('organizations', [
            'id' => $organization->id,
        ]);
    }

    public function test_member_cannot_delete_organization_from_the_index_page(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $organization = Organization::factory()->create(['created_by' => $owner->id]);
        $organization->members()->attach($owner->id, ['role' => 'owner']);
        $organization->members()->attach($member->id, ['role' => 'member']);

        $this->actingAs($member);

        Volt::test('pages.organizations.index')
            ->assertDontSee('Delete')
            ->call('deleteOrganization', $organization->id);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
        ]);
    }

    public function test_organizations_index_can_switch_between_grid_and_list_views(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['created_by' => $user->id, 'name' => 'Acme']);
        $organization->members()->attach($user->id, ['role' => 'owner']);

        $this->actingAs($user);

        Volt::test('pages.organizations.index')
            ->assertSet('viewMode', 'grid')
            ->call('setViewMode', 'list')
            ->assertSet('viewMode', 'list')
            ->assertSee('Add another organization')
            ->assertSee('Acme');
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
