# Slug Routing & Service Types Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace numeric org IDs in URLs with human-readable slugs (with 301 redirect history), and replace the hardcoded `service_type` enum with a managed `service_types` table supporting global and per-org custom types.

**Architecture:** Two independent features implemented sequentially. Slug routing adds a `slug` column + history table + `SlugService`, overrides Laravel model binding, and updates the settings save flow. Service types adds a `service_types` table, seeds global defaults, migrates existing credential rows, and adds UI for managing types at both admin and per-org level.

**Tech Stack:** Laravel 12, Livewire Volt, Tailwind CSS, Alpine.js, MySQL. Tests use PHPUnit via `php artisan test`.

**Spec:** `docs/superpowers/specs/2026-03-19-slug-routing-and-service-types-design.md`

---

## File Map

### Part A — Slug Routing

| Action | File |
|--------|------|
| Create | `app/Services/SlugService.php` |
| Create | `app/Exceptions/OldSlugRedirectException.php` |
| Create | `app/Models/OrganizationSlugHistory.php` |
| Create | `database/migrations/YYYY_MM_DD_add_slug_to_organizations_table.php` |
| Create | `database/migrations/YYYY_MM_DD_create_organization_slug_history_table.php` |
| Create | `tests/Feature/SlugRoutingTest.php` |
| Modify | `app/Models/Organization.php` |
| Modify | `bootstrap/app.php` |
| Modify | `app/Http/Middleware/VerifyOrganizationMembership.php` |
| Modify | `resources/views/livewire/pages/organizations/settings.blade.php` |

### Part B — Service Types

| Action | File |
|--------|------|
| Create | `app/Models/ServiceType.php` |
| Create | `app/Policies/ServiceTypePolicy.php` |
| Create | `database/migrations/YYYY_MM_DD_create_service_types_table.php` |
| Create | `database/migrations/YYYY_MM_DD_migrate_credentials_service_type_to_fk.php` |
| Create | `resources/views/livewire/pages/admin/service-types.blade.php` |
| Create | `resources/views/livewire/pages/organizations/service-types.blade.php` |
| Create | `tests/Feature/ServiceTypesTest.php` |
| Modify | `app/Models/Credential.php` |
| Modify | `app/Models/Organization.php` |
| Modify | `routes/web.php` |
| Modify | `resources/views/livewire/pages/organizations/show.blade.php` |
| Modify | `resources/views/livewire/pages/organizations/settings.blade.php` |
| Modify | `resources/views/livewire/pages/credentials/create.blade.php` |
| Modify | `resources/views/livewire/pages/credentials/edit.blade.php` |
| Modify | `resources/views/livewire/pages/admin/dashboard.blade.php` |

---

## Part A: Slug Routing

---

### Task 1: SlugService

**Files:**
- Create: `app/Services/SlugService.php`

- [ ] **Step 1: Create the service class**

```php
<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationSlugHistory;
use Illuminate\Support\Str;

class SlugService
{
    public static function generateUnique(string $name, ?int $excludeOrgId = null): string
    {
        $base = Str::slug($name);
        $slug = $base ?: 'org';
        $i    = 2;

        while (true) {
            $inOrgs = Organization::where('slug', $slug)
                ->when($excludeOrgId, fn($q) => $q->where('id', '!=', $excludeOrgId))
                ->exists();

            $inHistory = OrganizationSlugHistory::where('slug', $slug)->exists();

            if (!$inOrgs && !$inHistory) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
```

Note: `$base ?: 'org'` handles edge case where org name slugifies to empty string.

- [ ] **Step 2: Commit**

```bash
git add app/Services/SlugService.php
git commit -m "feat: add SlugService for unique slug generation"
```

---

### Task 2: OldSlugRedirectException + bootstrap/app.php registration

**Files:**
- Create: `app/Exceptions/OldSlugRedirectException.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Create the exception class**

```php
<?php

namespace App\Exceptions;

use App\Models\Organization;
use RuntimeException;

class OldSlugRedirectException extends RuntimeException
{
    public function __construct(public readonly Organization $organization)
    {
        parent::__construct('Organization slug has changed.');
    }
}
```

- [ ] **Step 2: Register the renderable handler in `bootstrap/app.php`**

Inside the existing `->withExceptions(function (Exceptions $exceptions): void { ... })` block, add AFTER the existing `$exceptions->respond(...)` call:

```php
use App\Exceptions\OldSlugRedirectException;

// Inside the withExceptions block, after the existing respond() call:
$exceptions->renderable(function (OldSlugRedirectException $ex, Request $request) {
    return redirect()->route('organizations.show', $ex->organization)->setStatusCode(301);
});
```

The top of `bootstrap/app.php` already imports `Illuminate\Http\Request`. Add the `OldSlugRedirectException` use statement at the top of the file with the other imports.

- [ ] **Step 3: Commit**

```bash
git add app/Exceptions/OldSlugRedirectException.php bootstrap/app.php
git commit -m "feat: add OldSlugRedirectException with 301 redirect handler"
```

---

### Task 3: OrganizationSlugHistory model

**Files:**
- Create: `app/Models/OrganizationSlugHistory.php`

- [ ] **Step 1: Create the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSlugHistory extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = ['organization_id', 'slug'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
```

`$timestamps = false` prevents Eloquent from trying to write `updated_at`. `const CREATED_AT = 'created_at'` ensures the insert timestamp is set when `$fillable` doesn't include it — pass `'created_at' => now()` explicitly on each insert.

- [ ] **Step 2: Commit**

```bash
git add app/Models/OrganizationSlugHistory.php
git commit -m "feat: add OrganizationSlugHistory model"
```

---

### Task 4: Migrations — slug column + history table

**Files:**
- Create: `database/migrations/..._add_slug_to_organizations_table.php`
- Create: `database/migrations/..._create_organization_slug_history_table.php`

- [ ] **Step 1: Generate migration stubs**

```bash
cd f:/Projects/laravel/passify
php artisan make:migration add_slug_to_organizations_table
php artisan make:migration create_organization_slug_history_table
```

- [ ] **Step 2: Fill in `add_slug_to_organizations_table`**

```php
<?php

use App\Models\Organization;
use App\Services\SlugService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add nullable first so we can populate
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Populate slugs for all existing orgs
        Organization::orderBy('id')->each(function ($org) {
            $org->timestamps = false;
            $org->slug = SlugService::generateUnique($org->name);
            $org->save();
        });

        // Now enforce NOT NULL + UNIQUE
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
```

- [ ] **Step 3: Fill in `create_organization_slug_history_table`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_slug_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                  ->constrained('organizations')
                  ->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamp('created_at')->nullable();
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_slug_history');
    }
};
```

- [ ] **Step 4: Run the migrations**

```bash
php artisan migrate
```

Expected: both migrations run without errors.

- [ ] **Step 5: Verify slug column was populated**

```bash
php artisan tinker --execute="echo App\Models\Organization::first()?->slug ?? 'No orgs yet';"
```

Expected: prints a slug string like `acme-digital` (or "No orgs yet" if db is empty).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat: add slug column to organizations and slug history table"
```

---

### Task 5: Update Organization model

**Files:**
- Modify: `app/Models/Organization.php`

- [ ] **Step 1: Add slug routing methods and relationships**

Add these to `app/Models/Organization.php`, after the existing `use` statements add:
```php
use App\Exceptions\OldSlugRedirectException;
use App\Models\OrganizationSlugHistory;
use App\Services\SlugService;
```

Add `'slug'` to `$fillable`:
```php
protected $fillable = ['name', 'slug', 'website_url', 'description', 'created_by'];
```

Add these methods to the class body:

```php
// ── Slug Routing ─────────────────────────────────────────────────────────────

public function getRouteKeyName(): string
{
    return 'slug';
}

public function resolveRouteBinding($value, $field = null): ?self
{
    // 1. Check current slug
    $org = static::where('slug', $value)->first();
    if ($org) {
        return $org;
    }

    // 2. Check slug history → redirect
    $history = OrganizationSlugHistory::where('slug', $value)
        ->with('organization')
        ->first();

    if ($history?->organization) {
        throw new OldSlugRedirectException($history->organization);
    }

    return null;  // Laravel converts null → 404
}

public function updateSlug(string $newName): void
{
    $newSlug = SlugService::generateUnique($newName, $this->id);

    if ($newSlug === $this->slug) {
        return;
    }

    // Archive the old slug
    OrganizationSlugHistory::create([
        'organization_id' => $this->id,
        'slug'            => $this->slug,
        'created_at'      => now(),
    ]);

    $this->update(['slug' => $newSlug]);
}

// ── Relationships ─────────────────────────────────────────────────────────────

public function slugHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(OrganizationSlugHistory::class);
}
```

- [ ] **Step 2: Run existing tests to make sure nothing is broken**

```bash
php artisan test
```

Expected: all existing tests pass (or note any pre-existing failures).

- [ ] **Step 3: Commit**

```bash
git add app/Models/Organization.php
git commit -m "feat: add slug routing to Organization model"
```

---

### Task 6: Fix VerifyOrganizationMembership middleware

**Files:**
- Modify: `app/Http/Middleware/VerifyOrganizationMembership.php`

- [ ] **Step 1: Replace the ID-based fallback with a slug-based lookup**

Find line 23 in `app/Http/Middleware/VerifyOrganizationMembership.php`:
```php
$organization = $organization ? Organization::find($organization) : null;
```

Replace with:
```php
$organization = $organization ? Organization::where('slug', $organization)->first() : null;
```

No other changes needed.

- [ ] **Step 2: Commit**

```bash
git add app/Http/Middleware/VerifyOrganizationMembership.php
git commit -m "fix: update org membership middleware to resolve by slug"
```

---

### Task 7: Write and run slug routing feature tests

**Files:**
- Create: `tests/Feature/SlugRoutingTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function orgMember(Organization $org): User
    {
        $user = User::factory()->create();
        $org->members()->attach($user->id, ['role' => 'owner']);
        return $user;
    }

    public function test_org_url_uses_slug(): void
    {
        $org  = Organization::factory()->create(['name' => 'Acme Digital', 'slug' => 'acme-digital']);
        $user = $this->orgMember($org);

        $this->actingAs($user)
             ->get(route('organizations.show', $org))
             ->assertOk()
             ->assertSee('Acme Digital');

        $this->assertStringContainsString('acme-digital', route('organizations.show', $org));
    }

    public function test_old_slug_redirects_301(): void
    {
        $org  = Organization::factory()->create(['name' => 'New Name', 'slug' => 'new-name']);
        $user = $this->orgMember($org);

        OrganizationSlugHistory::create([
            'organization_id' => $org->id,
            'slug'            => 'old-name',
            'created_at'      => now(),
        ]);

        $this->actingAs($user)
             ->get('/organizations/old-name')
             ->assertRedirect(route('organizations.show', $org))
             ->assertStatus(301);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->get('/organizations/does-not-exist')
             ->assertNotFound();
    }

    public function test_slug_generated_on_org_creation(): void
    {
        // Pass slug explicitly — factory uses faker company name, not the name override
        $org = Organization::factory()->create(['name' => 'Test Org', 'slug' => 'test-org']);
        $this->assertSame('test-org', $org->slug);
    }

    public function test_slug_collision_appends_number(): void
    {
        Organization::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
        $org2 = Organization::factory()->create(['name' => 'Acme']);
        $this->assertSame('acme-2', $org2->slug);
    }
}
```

Note: `Organization::factory()` will need a `slug` state or auto-slug. Add to `OrganizationFactory` in step 2.

- [ ] **Step 2: Add slug to OrganizationFactory (if one exists)**

Check if `database/factories/OrganizationFactory.php` exists:

```bash
ls database/factories/
```

If `OrganizationFactory.php` exists, add `'slug'` to its `definition()`:
```php
'slug' => \Illuminate\Support\Str::slug($this->faker->unique()->company()),
```

If no factory exists yet, create `database/factories/OrganizationFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();
        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'website_url' => $this->faker->optional()->url(),
            'description' => $this->faker->optional()->sentence(),
            'created_by'  => User::factory(),
        ];
    }
}
```

Also add `use HasFactory;` to `Organization` model and `use App\Models\Organization;` at the top of the factory if not already there.

- [ ] **Step 3: Run the slug tests**

```bash
php artisan test --filter=SlugRoutingTest
```

Expected: 5 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/SlugRoutingTest.php database/factories/OrganizationFactory.php
git commit -m "test: add slug routing feature tests"
```

---

### Task 8: Settings page — update slug on name change

**Files:**
- Modify: `resources/views/livewire/pages/organizations/settings.blade.php`

- [ ] **Step 1: Update the `save()` method in the PHP section**

Replace the existing `save()` method:

```php
public function save(): void
{
    $this->authorize('update', $this->organization);

    $validated = $this->validate([
        'name'        => ['required', 'string', 'max:255'],
        'website_url' => ['nullable', 'url', 'max:255'],
        'description' => ['nullable', 'string', 'max:1000'],
    ]);

    // Update slug if name changed (archives old slug → 301 redirect)
    if ($validated['name'] !== $this->organization->name) {
        $this->organization->updateSlug($validated['name']);
    }

    $this->organization->update($validated);

    session()->flash('status', 'Settings saved.');

    // Redirect to the new slug URL so the browser address bar updates
    $this->redirect(route('organizations.settings', $this->organization), navigate: true);
}
```

- [ ] **Step 2: Test the slug update flow manually (or write a quick test)**

Start the dev server, go to an org's settings, rename it, save — the URL in the address bar should update to the new slug. The old URL should redirect.

Run tests:
```bash
php artisan test
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/pages/organizations/settings.blade.php
git commit -m "feat: update org slug when name changes in settings"
```

---

## Part B: Service Types

---

### Task 9: ServiceType model

**Files:**
- Create: `app/Models/ServiceType.php`

- [ ] **Step 1: Create the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'organization_id', 'created_by'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('organization_id');
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    /**
     * Returns global types + org-specific types merged, globals first.
     */
    public static function forContext(Organization $org)
    {
        return static::where(function ($q) use ($org) {
            $q->whereNull('organization_id')
              ->orWhere('organization_id', $org->id);
        })
        ->orderByRaw('CASE WHEN organization_id IS NULL THEN 0 ELSE 1 END')
        ->orderBy('name')
        ->get();
    }

    public static function colorPalette(): array
    {
        return [
            'bg-blue-100 text-blue-700',
            'bg-purple-100 text-purple-700',
            'bg-pink-100 text-pink-700',
            'bg-orange-100 text-orange-700',
            'bg-cyan-100 text-cyan-700',
            'bg-emerald-100 text-emerald-700',
            'bg-yellow-100 text-yellow-700',
            'bg-gray-100 text-gray-600',
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/ServiceType.php
git commit -m "feat: add ServiceType model"
```

---

### Task 10: ServiceTypePolicy

**Files:**
- Create: `app/Policies/ServiceTypePolicy.php`

- [ ] **Step 1: Generate and fill the policy**

```bash
php artisan make:policy ServiceTypePolicy --model=ServiceType
```

Replace the generated content:

```php
<?php

namespace App\Policies;

use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceTypePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return null;
    }

    /** Anyone authenticated can view. */
    public function view(User $user, ServiceType $serviceType): bool
    {
        return true;
    }

    /** Global types: super admin only (handled by before()). Org types: org owner. */
    public function create(User $user, ServiceType $serviceType): bool
    {
        if (is_null($serviceType->organization_id)) {
            return false; // only super admin — handled by before()
        }
        return $user->isOwnerOfOrganization($serviceType->organization);
    }

    public function update(User $user, ServiceType $serviceType): bool
    {
        if (is_null($serviceType->organization_id)) {
            return false;
        }
        return $user->isOwnerOfOrganization($serviceType->organization);
    }

    public function delete(User $user, ServiceType $serviceType): bool
    {
        if (is_null($serviceType->organization_id)) {
            return false;
        }
        return $user->isOwnerOfOrganization($serviceType->organization);
    }
}
```

- [ ] **Step 2: Register the policy in `app/Providers/AppServiceProvider.php` (if needed)**

Check if auto-discovery is configured. In Laravel 12, policies are auto-discovered. No manual registration needed unless auto-discovery is disabled.

- [ ] **Step 3: Commit**

```bash
git add app/Policies/ServiceTypePolicy.php
git commit -m "feat: add ServiceTypePolicy"
```

---

### Task 11: Create service_types migration

**Files:**
- Create: `database/migrations/..._create_service_types_table.php`

- [ ] **Step 1: Generate and fill the migration**

```bash
php artisan make:migration create_service_types_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');
            $table->foreignId('organization_id')
                  ->nullable()
                  ->constrained('organizations')
                  ->cascadeOnDelete();
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/
git commit -m "feat: create service_types table"
```

---

### Task 12: Data migration — seed globals + remap credentials

**Files:**
- Create: `database/migrations/..._migrate_credentials_service_type_to_fk.php`

- [ ] **Step 1: Generate the migration**

```bash
php artisan make:migration migrate_credentials_service_type_to_fk
```

- [ ] **Step 2: Fill in the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Global seed data
    private array $globals = [
        ['name' => 'Hosting',      'color' => 'bg-blue-100 text-blue-700',     'enum' => 'hosting'],
        ['name' => 'Domain',       'color' => 'bg-purple-100 text-purple-700', 'enum' => 'domain'],
        ['name' => 'Email',        'color' => 'bg-pink-100 text-pink-700',     'enum' => 'email'],
        ['name' => 'Database',     'color' => 'bg-orange-100 text-orange-700', 'enum' => 'database'],
        ['name' => 'Social Media', 'color' => 'bg-cyan-100 text-cyan-700',     'enum' => 'social_media'],
        ['name' => 'Analytics',    'color' => 'bg-emerald-100 text-emerald-700','enum' => 'analytics'],
        ['name' => 'Other',        'color' => 'bg-gray-100 text-gray-600',     'enum' => 'other'],
    ];

    public function up(): void
    {
        // 1. Add nullable service_type_id to credentials
        Schema::table('credentials', function (Blueprint $table) {
            $table->unsignedBigInteger('service_type_id')->nullable()->after('organization_id');
        });

        // 2. Seed global service types
        $now = now();
        foreach ($this->globals as $type) {
            DB::table('service_types')->insert([
                'name'            => $type['name'],
                'color'           => $type['color'],
                'organization_id' => null,
                'created_by'      => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // 3. Map existing enum values to new FK ids
        foreach ($this->globals as $type) {
            $typeId = DB::table('service_types')->where('name', $type['name'])->value('id');
            DB::table('credentials')
                ->where('service_type', $type['enum'])
                ->update(['service_type_id' => $typeId]);
        }

        // 4. Set service_type_id NOT NULL (all rows should have a value now)
        Schema::table('credentials', function (Blueprint $table) {
            $table->unsignedBigInteger('service_type_id')->nullable(false)->change();
        });

        // 5. Add FK constraint (RESTRICT — prevents deleting types with credentials)
        Schema::table('credentials', function (Blueprint $table) {
            $table->foreign('service_type_id')
                  ->references('id')
                  ->on('service_types')
                  ->restrictOnDelete();
        });

        // 6. Add index to replace the dropped enum index
        Schema::table('credentials', function (Blueprint $table) {
            $table->index('service_type_id');
        });

        // 7. Drop old enum column
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn('service_type');
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be safely reversed.');
    }
};
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate
```

Expected: migrates cleanly. If any credentials exist with an enum value not in the list, they'll remain with `service_type_id = null`, which will block the NOT NULL change. Investigate with:
```bash
php artisan tinker --execute="echo DB::table('credentials')->whereNull('service_type_id')->count();"
```

- [ ] **Step 4: Verify**

```bash
php artisan tinker --execute="echo App\Models\ServiceType::count() . ' global types';"
```

Expected: `7 global types`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: data migration - seed global service types and remap credentials FK"
```

---

### Task 13: Update Credential and Organization models

**Files:**
- Modify: `app/Models/Credential.php`
- Modify: `app/Models/Organization.php`

- [ ] **Step 1: Update `Credential` model**

Replace `$fillable` and casts, add relationship:

```php
protected $fillable = ['organization_id', 'service_type_id', 'name', 'website_url', 'email', 'password', 'note'];

protected $casts = [];  // remove old service_type cast

public function serviceType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(ServiceType::class);
}
```

Add `use App\Models\ServiceType;` at the top if not already imported (it's in same namespace, so just `ServiceType` reference is fine).

- [ ] **Step 2: Add `serviceTypes()` relationship to `Organization` model**

Add to `app/Models/Organization.php`:

```php
public function serviceTypes(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(ServiceType::class);
}
```

- [ ] **Step 3: Run tests**

```bash
php artisan test
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/Credential.php app/Models/Organization.php
git commit -m "feat: update Credential and Organization models for service_type_id"
```

---

### Task 14: Add service types routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add both new routes**

Inside the `org.member` middleware group (after the existing `credentials.edit` route):

```php
Volt::route('/organizations/{organization}/service-types', 'pages.organizations.service-types')
    ->name('organizations.service-types');
```

Inside the `super.admin` middleware prefix group (after the existing `admin.users` route):

```php
Volt::route('/service-types', 'pages.admin.service-types')
    ->name('service-types');  // full name becomes 'admin.service-types' due to the prefix group's ->name('admin.')
```

- [ ] **Step 2: Commit**

```bash
git add routes/web.php
git commit -m "feat: add service-types routes for org and admin"
```

---

### Task 15: Update organizations/show.blade.php — dynamic filter

**Files:**
- Modify: `resources/views/livewire/pages/organizations/show.blade.php`

- [ ] **Step 1: Update the PHP section**

Add import at top:
```php
use App\Models\ServiceType;
```

Change `$filterType` property type from `string` to `int|string` (use `''` as "all"):
```php
public string $filterType = '';
```

Change to:
```php
public int|string $filterType = '';
```

Add a `#[Computed]` method for the available types:
```php
#[Computed]
public function serviceTypes()
{
    return ServiceType::forContext($this->organization);
}
```

Update the `credentials()` computed method — change the filter line:
```php
// OLD:
->when($this->filterType, fn($q) => $q->where('service_type', $this->filterType))

// NEW:
->when($this->filterType !== '', fn($q) => $q->where('service_type_id', $this->filterType))
```

Also update `credentials()` to eager-load the service type:
```php
return $this->organization->credentials()
    ->with('serviceType')
    ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
    ->when($this->filterType !== '', fn($q) => $q->where('service_type_id', $this->filterType))
    ->orderBy('name')
    ->get();
```

Remove `serviceTypeLabel()` and `serviceTypeBadge()` methods entirely.

- [ ] **Step 2: Update the filter bar Blade template**

Replace the hardcoded filter buttons section:

```blade
{{-- Service Type Filter --}}
<div class="flex flex-wrap gap-1.5">
    <button wire:click="$set('filterType', '')"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $filterType === '' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        All
    </button>
    @foreach($this->serviceTypes as $type)
        <button wire:click="$set('filterType', {{ $type->id }})"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $filterType == $type->id ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ $type->name }}
        </button>
    @endforeach
</div>
```

Replace badge display in the credential card — find the `serviceTypeBadge` and `serviceTypeLabel` calls and replace with:

```blade
<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $credential->serviceType->color }}">
    {{ $credential->serviceType->name }}
</span>
```

- [ ] **Step 3: Add service-types tab to org nav (in the header buttons)**

In the header area where Members and Settings links appear, add a new tab link for all members (not just owners):

```blade
<a href="{{ route('organizations.service-types', $organization) }}" wire:navigate
   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.169.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
    </svg>
    Types
</a>
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/pages/organizations/show.blade.php
git commit -m "feat: dynamic service type filter on org show page"
```

---

### Task 16: Update credential create/edit forms

**Files:**
- Modify: `resources/views/livewire/pages/credentials/create.blade.php`
- Modify: `resources/views/livewire/pages/credentials/edit.blade.php`

- [ ] **Step 1: Update `create.blade.php` PHP section**

Add import: `use App\Models\ServiceType;`

Change the property:
```php
// OLD:
public string $service_type = 'other';

// NEW:
public int $service_type_id = 0;
```

Remove the `serviceTypes()` method.

Add a `#[Computed]` method:
```php
#[Computed]
public function serviceTypes()
{
    return ServiceType::forContext($this->organization);
}
```

Add `use Livewire\Attributes\Computed;` to the `use` block.

In `mount()`, set the default to the first available type:
```php
public function mount(Organization $organization): void
{
    $this->organization = $organization;
    $this->authorize('create', [Credential::class, $organization]);
    $this->service_type_id = ServiceType::global()->orderBy('name')->value('id') ?? 0;
}
```

Update `save()` validation:
```php
$validated = $this->validate([
    'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
    'name'            => ['required', 'string', 'max:255'],
    'website_url'     => ['nullable', 'url', 'max:255'],
    'email'           => ['nullable', 'email', 'max:255'],
    'password'        => ['required', 'string', 'max:1000'],
    'note'            => ['nullable', 'string', 'max:2000'],
]);
```

- [ ] **Step 2: Update `create.blade.php` Blade template**

Replace the service type `<select>`:
```blade
<div>
    <x-input-label for="service_type_id" value="Service Type *"/>
    <select wire:model="service_type_id" id="service_type_id"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        @foreach($this->serviceTypes as $type)
            <option value="{{ $type->id }}">{{ $type->name }}{{ $type->organization_id ? ' (custom)' : '' }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('service_type_id')" class="mt-2"/>
</div>
```

- [ ] **Step 3: Update `edit.blade.php` PHP section**

Apply the same changes as create.blade.php, but:
- Property: `public int $service_type_id = 0;`
- In `mount()`: `$this->service_type_id = $credential->service_type_id;`
- Remove `public string $service_type = '';` line
- Update validation to use `service_type_id`
- Update `save()` to pass `service_type_id` in `$validated`

- [ ] **Step 4: Update `edit.blade.php` Blade template**

Same select replacement as create.blade.php.

- [ ] **Step 5: Run tests**

```bash
php artisan test
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/pages/credentials/create.blade.php \
        resources/views/livewire/pages/credentials/edit.blade.php
git commit -m "feat: update credential forms to use service_type_id"
```

---

### Task 17: Per-org service types page

**Files:**
- Create: `resources/views/livewire/pages/organizations/service-types.blade.php`

- [ ] **Step 1: Create the component**

```php
<?php

use App\Models\Organization;
use App\Models\ServiceType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Organization $organization;
    public string $newName  = '';
    public string $newColor = 'bg-blue-100 text-blue-700';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->authorize('view', $organization);
    }

    public function isOwner(): bool
    {
        return auth()->user()->isOwnerOfOrganization($this->organization)
            || auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceType::forContext($this->organization);
    }

    public function colorPalette(): array
    {
        return ServiceType::colorPalette();
    }

    public function addType(): void
    {
        $this->authorize('update', $this->organization);

        $this->validate([
            'newName'  => ['required', 'string', 'max:255'],
            'newColor' => ['required', 'string', 'in:' . implode(',', ServiceType::colorPalette())],
        ]);

        ServiceType::create([
            'name'            => $this->newName,
            'color'           => $this->newColor,
            'organization_id' => $this->organization->id,
            'created_by'      => auth()->id(),
        ]);

        $this->newName  = '';
        $this->newColor = 'bg-blue-100 text-blue-700';
        session()->flash('status', 'Service type added.');
    }

    public function deleteType(ServiceType $serviceType): void
    {
        // Only org-specific types can be deleted here
        if (is_null($serviceType->organization_id)) {
            $this->addError('delete', 'Global types cannot be deleted from here.');
            return;
        }

        $this->authorize('delete', $serviceType);

        if ($serviceType->credentials()->exists()) {
            $this->addError('delete', 'Cannot delete "' . $serviceType->name . '" — it has credentials assigned.');
            return;
        }

        $serviceType->delete();
        session()->flash('status', 'Service type deleted.');
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('organizations.show', $organization) }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Service Types</h2>
            <p class="text-sm text-gray-500">{{ $organization->name }}</p>
        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @error('delete')
            <div class="bg-red-50 text-red-700 border border-red-200 rounded-xl px-4 py-3 text-sm font-medium">{{ $message }}</div>
        @enderror

        {{-- Type List --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-base font-semibold text-gray-900">Available Types</h3>
                <p class="text-sm text-gray-500 mt-0.5">Global types are shared across all organizations. Custom types are specific to this org.</p>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($this->serviceTypes as $type)
                    <div class="px-6 py-3 flex items-center gap-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $type->color }}">
                            {{ $type->name }}
                        </span>
                        <span class="text-xs text-gray-400 flex-1">
                            {{ is_null($type->organization_id) ? 'Global' : 'Custom' }}
                        </span>
                        @if($this->isOwner() && !is_null($type->organization_id))
                            <button wire:click="deleteType({{ $type->id }})"
                                    wire:confirm="Delete '{{ $type->name }}'?"
                                    class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Add Custom Type (owners only) --}}
        @if($this->isOwner())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-semibold text-gray-900">Add Custom Type</h3>
                </div>
                <form wire:submit="addType" class="p-6 space-y-4">
                    <div>
                        <x-input-label for="newName" value="Type Name *"/>
                        <x-text-input wire:model="newName" id="newName" type="text" class="mt-1 block w-full" placeholder="e.g. CRM, VPN, FTP"/>
                        <x-input-error :messages="$errors->get('newName')" class="mt-2"/>
                    </div>
                    <div>
                        <x-input-label value="Color *"/>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($this->colorPalette() as $color)
                                <button type="button" wire:click="$set('newColor', '{{ $color }}')"
                                        class="w-8 h-8 rounded-lg border-2 transition {{ $newColor === $color ? 'border-gray-800 scale-110' : 'border-transparent' }} {{ $color }}">
                                </button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('newColor')" class="mt-2"/>
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <x-primary-button wire:loading.attr="disabled" wire:loading.class="opacity-75">
                            <span wire:loading.remove wire:target="addType">Add Type</span>
                            <span wire:loading wire:target="addType">Adding…</span>
                        </x-primary-button>
                        @if($newName)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $newColor }}">{{ $newName }}</span>
                            <span class="text-xs text-gray-400">Preview</span>
                        @endif
                    </div>
                </form>
            </div>
        @endif

    </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/livewire/pages/organizations/service-types.blade.php
git commit -m "feat: per-org service types management page"
```

---

### Task 18: Admin service types page

**Files:**
- Create: `resources/views/livewire/pages/admin/service-types.blade.php`

- [ ] **Step 1: Create the component**

```php
<?php

use App\Models\ServiceType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $newName  = '';
    public string $newColor = 'bg-blue-100 text-blue-700';

    #[Computed]
    public function globalTypes()
    {
        return ServiceType::global()->orderBy('name')->get();
    }

    public function colorPalette(): array
    {
        return ServiceType::colorPalette();
    }

    public function addType(): void
    {
        $this->validate([
            'newName'  => ['required', 'string', 'max:255'],
            'newColor' => ['required', 'string', 'in:' . implode(',', ServiceType::colorPalette())],
        ]);

        ServiceType::create([
            'name'            => $this->newName,
            'color'           => $this->newColor,
            'organization_id' => null,
            'created_by'      => auth()->id(),
        ]);

        $this->newName  = '';
        $this->newColor = 'bg-blue-100 text-blue-700';
        session()->flash('status', 'Global service type added.');
    }

    public function deleteType(ServiceType $serviceType): void
    {
        if ($serviceType->credentials()->exists()) {
            $this->addError('delete', 'Cannot delete "' . $serviceType->name . '" — it has credentials assigned.');
            return;
        }

        $serviceType->delete();
        session()->flash('status', 'Service type deleted.');
    }
}; ?>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Global Service Types</h2>
</x-slot>

<div class="py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @error('delete')
            <div class="bg-red-50 text-red-700 border border-red-200 rounded-xl px-4 py-3 text-sm font-medium">{{ $message }}</div>
        @enderror

        {{-- Global Types List --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-base font-semibold text-gray-900">Global Types</h3>
                <p class="text-sm text-gray-500 mt-0.5">Available to all organizations.</p>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($this->globalTypes as $type)
                    <div class="px-6 py-3 flex items-center gap-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $type->color }}">
                            {{ $type->name }}
                        </span>
                        <span class="text-xs text-gray-400 flex-1">{{ $type->credentials_count ?? $type->credentials()->count() }} credentials</span>
                        <button wire:click="deleteType({{ $type->id }})"
                                wire:confirm="Delete '{{ $type->name }}'? This will fail if credentials use this type."
                                class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-400">No global service types found.</div>
                @endforelse
            </div>
        </div>

        {{-- Add Global Type --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-base font-semibold text-gray-900">Add Global Type</h3>
            </div>
            <form wire:submit="addType" class="p-6 space-y-4">
                <div>
                    <x-input-label for="newName" value="Type Name *"/>
                    <x-text-input wire:model="newName" id="newName" type="text" class="mt-1 block w-full" placeholder="e.g. VPN, CDN, Monitoring"/>
                    <x-input-error :messages="$errors->get('newName')" class="mt-2"/>
                </div>
                <div>
                    <x-input-label value="Color *"/>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($this->colorPalette() as $color)
                            <button type="button" wire:click="$set('newColor', '{{ $color }}')"
                                    class="w-8 h-8 rounded-lg border-2 transition {{ $newColor === $color ? 'border-gray-800 scale-110' : 'border-transparent' }} {{ $color }}">
                            </button>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('newColor')" class="mt-2"/>
                </div>
                <div class="flex items-center gap-3 pt-1">
                    <x-primary-button wire:loading.attr="disabled" wire:loading.class="opacity-75">
                        <span wire:loading.remove wire:target="addType">Add Global Type</span>
                        <span wire:loading wire:target="addType">Adding…</span>
                    </x-primary-button>
                    @if($newName)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $newColor }}">{{ $newName }}</span>
                        <span class="text-xs text-gray-400">Preview</span>
                    @endif
                </div>
            </form>
        </div>

    </div>
</div>
```

- [ ] **Step 2: Add link to admin dashboard quick nav**

In `resources/views/livewire/pages/admin/dashboard.blade.php`, add a third card to the Quick Nav grid after the existing two cards:

```blade
<a href="{{ route('admin.service-types') }}" wire:navigate
   class="group flex items-center gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-emerald-200 hover:shadow-md transition-all duration-200 cursor-pointer">
    <div class="w-11 h-11 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center shrink-0 transition-colors duration-200">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.169.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
        </svg>
    </div>
    <div class="flex-1 min-w-0">
        <p class="font-bold text-gray-800 group-hover:text-emerald-600 transition-colors duration-200">Service Types</p>
        <p class="text-sm text-gray-400 mt-0.5">Manage global credential categories</p>
    </div>
    <svg class="w-5 h-5 text-gray-300 group-hover:text-emerald-400 transition-colors duration-200 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
    </svg>
</a>
```

Also change the grid from `sm:grid-cols-2` to `sm:grid-cols-3` to accommodate the third card.

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/pages/admin/service-types.blade.php \
        resources/views/livewire/pages/admin/dashboard.blade.php
git commit -m "feat: admin global service types management page"
```

---

### Task 19: Final test pass

- [ ] **Step 1: Run all tests**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 2: Smoke test in browser**

1. Navigate to an org — URL should show slug e.g. `/organizations/acme-digital`
2. Rename org in Settings — URL should update, old URL should 301 redirect
3. Go to org → Types tab — should see global types listed
4. Add a custom type — should appear below globals
5. Add a credential — dropdown should show all types (globals + custom)
6. Filter by type on org show page — should work
7. As super admin: visit `/admin/service-types` — should list and allow adding global types

- [ ] **Step 3: Final commit**

```bash
git add -A
git commit -m "feat: complete slug routing and service types implementation"
```
