# Design: Organization Slug Routing & Service Types Table

**Date:** 2026-03-19
**Status:** Approved

---

## Overview

Two independent features:

1. **Organization slug routing** — replace numeric ID in URLs (`/organizations/4`) with a human-readable slug (`/organizations/acme-digital`). Old slugs redirect permanently so no existing links break.
2. **Service types table** — replace the hardcoded `service_type` enum on `credentials` with a managed `service_types` table supporting global types (super admin) and per-org custom types (owners).

---

## Feature 1: Organization Slug Routing

### Database

**`organizations` table — add column:**
```
slug  VARCHAR(255)  UNIQUE  NOT NULL
```
Generated from `name` via `Str::slug()`. Uniqueness enforced by appending `-2`, `-3`, etc. (see Slug Generation below).

**New `organization_slug_history` table:**
```
id               BIGINT UNSIGNED  PK
organization_id  BIGINT UNSIGNED  FK → organizations (cascade delete)
slug             VARCHAR(255)     UNIQUE NOT NULL
created_at       TIMESTAMP        NULL
INDEX(organization_id)
```
Stores every slug an org has ever had so old URLs redirect permanently. No `updated_at` — records are insert-only.

### Model: Organization

**`getRouteKeyName()`** returns `'slug'`.

**`resolveRouteBinding($value, $field = null)`** override:
1. Attempt `Organization::where('slug', $value)->first()` — return if found.
2. If not found, check `OrganizationSlugHistory::where('slug', $value)->with('organization')->first()`.
3. If history record found — throw a new `OldSlugRedirectException($history->organization)` carrying the current org model.
4. If neither found — return `null` (Laravel converts null to 404 automatically).

**`OldSlugRedirectException`** (new class at `app/Exceptions/OldSlugRedirectException.php`):
- Holds the `Organization` model instance.
- Registered in `bootstrap/app.php` inside the existing `->withExceptions(function (Exceptions $exceptions): void { ... })` block:

```php
$exceptions->renderable(function (OldSlugRedirectException $ex, Request $request) {
    return redirect()->route('organizations.show', $ex->organization)->setStatusCode(301);
});
```

Note: `301` must be set via `->setStatusCode(301)` on the response — `renderable()` does not accept a status code argument.

**`slugHistory()`** hasMany `OrganizationSlugHistory`.

### Slug Generation

`SlugService` class at `app/Services/SlugService.php` with a static method:

```php
public static function generateUnique(string $name, ?int $excludeOrgId = null): string
{
    $base = Str::slug($name);
    $slug = $base;
    $i    = 2;

    while (true) {
        $inOrgs    = Organization::where('slug', $slug)
                        ->when($excludeOrgId, fn($q) => $q->where('id', '!=', $excludeOrgId))
                        ->exists();
        $inHistory = OrganizationSlugHistory::where('slug', $slug)->exists();

        if (!$inOrgs && !$inHistory) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}
```

Uniqueness is checked across **both** `organizations.slug` and `organization_slug_history.slug` to prevent a new org from claiming a slug that currently redirects to another org.

### Name Change Flow (Settings page)

When org name is saved in `organizations/settings.blade.php`:
1. Call `SlugService::generateUnique($newName, $org->id)` to get the candidate slug.
2. If candidate === current `$org->slug` → no slug change needed.
3. If different:
   a. Insert current slug into `organization_slug_history` (`organization_id`, `slug`, `created_at`).
   b. Update `organizations.slug` to the new slug.

### Routes

No route definition changes — all existing `{organization}` parameters continue to work via the overridden `resolveRouteBinding()`.

All `route('organizations.*', $organization)` calls in views automatically use the current slug because `getRouteKeyName()` returns `'slug'`.

### VerifyOrganizationMembership Middleware Change

The existing middleware has a fallback branch (line 23) that runs when route model binding has not yet resolved the model:

```php
// BEFORE (broken after slug routing):
$organization = $organization ? Organization::find($organization) : null;

// AFTER (slug-aware):
$organization = $organization ? Organization::where('slug', $organization)->first() : null;
```

`Organization::find()` performs a primary-key lookup — it will return null for a slug string. The fix replaces it with a slug-based query. The rest of the middleware (null → 404, member check) remains unchanged.

### Migration Strategy

1. Migration `add_slug_to_organizations_table`: adds nullable `slug` column, populates all existing orgs using `SlugService::generateUnique($org->name)` (accessed as a static call, fully qualified), then sets column to NOT NULL + adds UNIQUE index.
2. Migration `create_organization_slug_history_table`: creates the history table.

**Migration pseudocode for slug population:**
```php
use App\Services\SlugService;
// Run after Organization model is available
Organization::orderBy('id')->each(function ($org) {
    $org->timestamps = false;
    $org->update(['slug' => SlugService::generateUnique($org->name)]);
});
```

### `OrganizationSlugHistory` Model

```php
public $timestamps = false;   // table has only created_at, no updated_at
const CREATED_AT = 'created_at';

protected $fillable = ['organization_id', 'slug'];

public function organization(): BelongsTo
{
    return $this->belongsTo(Organization::class);
}
```

---

## Feature 2: Service Types

### Database

**New `service_types` table:**
```
id               BIGINT UNSIGNED   PK
name             VARCHAR(255)      NOT NULL
color            VARCHAR(255)      NOT NULL   -- Tailwind badge class pair e.g. "bg-blue-100 text-blue-700"
organization_id  BIGINT UNSIGNED   NULLABLE   FK → organizations (cascade delete)
                                              -- NULL = global (super admin managed)
                                              -- set  = org-specific custom type
created_by       BIGINT UNSIGNED   NULLABLE   FK → users (set null on delete)
timestamps
INDEX(organization_id)
```

`created_by` is NULLABLE because the FK uses SET NULL on user delete.

**`credentials` table — changes:**
- Add `service_type_id  BIGINT UNSIGNED  FK → service_types (RESTRICT)` (RESTRICT prevents deleting a type that has credentials)
- Drop `service_type` enum column

### Seeded Global Service Types

The data migration seeds these 7 rows with `organization_id = NULL`:

| name         | color                            | maps from enum value |
|--------------|----------------------------------|----------------------|
| Hosting      | `bg-blue-100 text-blue-700`      | `hosting`            |
| Domain       | `bg-purple-100 text-purple-700`  | `domain`             |
| Email        | `bg-pink-100 text-pink-700`      | `email`              |
| Database     | `bg-orange-100 text-orange-700`  | `database`           |
| Social Media | `bg-cyan-100 text-cyan-700`      | `social_media`       |
| Analytics    | `bg-emerald-100 text-emerald-700`| `analytics`          |
| Other        | `bg-gray-100 text-gray-600`      | `other`              |

### Data Migration Order

Migration `migrate_credentials_service_type_to_fk`:
1. Add `service_type_id` column (nullable initially).
2. Insert the 7 global `service_types` rows above.
3. Map each existing credential's enum string to the new FK:
   ```php
   $map = [
       'hosting'      => ServiceType::where('name', 'Hosting')->value('id'),
       'domain'       => ServiceType::where('name', 'Domain')->value('id'),
       'email'        => ServiceType::where('name', 'Email')->value('id'),
       'database'     => ServiceType::where('name', 'Database')->value('id'),
       'social_media' => ServiceType::where('name', 'Social Media')->value('id'),
       'analytics'    => ServiceType::where('name', 'Analytics')->value('id'),
       'other'        => ServiceType::where('name', 'Other')->value('id'),
   ];
   foreach ($map as $enumVal => $typeId) {
       DB::table('credentials')->where('service_type', $enumVal)->update(['service_type_id' => $typeId]);
   }
   ```
4. Set `service_type_id` to NOT NULL.
5. Add RESTRICT FK constraint.
6. Add `INDEX(service_type_id)` on `credentials` (replacing the dropped `INDEX(service_type)`).
7. Drop `service_type` enum column.

**Rollback note:** The `down()` for this migration is complex — it requires re-adding the enum column, reverse-mapping `service_type_id` back to enum strings, dropping the FK, and dropping `service_type_id`. Given this is a production data migration, treat it as effectively irreversible. The `down()` method should throw a `RuntimeException('This migration cannot be safely reversed.')` to prevent accidental rollback.

### Models

**`ServiceType` model:**
```php
protected $fillable = ['name', 'color', 'organization_id', 'created_by'];

public function organization(): BelongsTo { ... }   // nullable
public function credentials(): HasMany { ... }
public function scopeGlobal($q) { return $q->whereNull('organization_id'); }
public function scopeForOrg($q, int $orgId) { return $q->where('organization_id', $orgId); }
```

**`Credential` model:**
- Remove `service_type` from `$fillable`, add `service_type_id`
- Remove `service_type` cast
- Add `serviceType()` belongsTo `ServiceType`

**`Organization` model:**
- Add `serviceTypes()` hasMany `ServiceType`

### Service Type Resolution Query

```php
ServiceType::where(function ($q) use ($org) {
    $q->whereNull('organization_id')
      ->orWhere('organization_id', $org->id);
})
->orderByRaw('CASE WHEN organization_id IS NULL THEN 0 ELSE 1 END')  // globals first, standard SQL
->orderBy('name')
->get();
```

Using `CASE WHEN` instead of `IS NULL` for portability across MySQL and SQLite (used in tests).

### Authorization — ServiceTypePolicy

**Global types** (`organization_id = null`):
- `create`, `update`, `delete`: super admin only
- `view`: any authenticated user

**Org-specific types** (`organization_id = $org->id`):
- `create`: org owner OR super admin
- `update`, `delete`: org owner OR super admin
- `view`: any org member

### Delete Guard

Before attempting deletion in the Volt component, check:
```php
if ($serviceType->credentials()->exists()) {
    $this->addError('delete', 'Cannot delete a type that has credentials assigned to it.');
    return;
}
```
The DB RESTRICT FK constraint is the safety net; the application-level check provides user-friendly messaging.

### UI — Filter Bar on `organizations/show.blade.php`

**`$filterType`** changes from a string enum value to an integer (`service_type_id`). The filter query changes from:
```php
->when($this->filterType, fn($q) => $q->where('service_type', $this->filterType))
```
to:
```php
->when($this->filterType, fn($q) => $q->where('service_type_id', $this->filterType))
```

Filter buttons are generated dynamically from the merged global + org types query. The hardcoded array in the blade template is replaced with a `#[Computed]` method returning `ServiceType` models. Each button displays `$type->name` and is keyed by `$type->id`.

`serviceTypeLabel()` and `serviceTypeBadge()` helper methods on the component are removed; the view reads `$credential->serviceType->name` and `$credential->serviceType->color` directly.

### UI — Admin Panel: Global Service Types

New route inside the existing `super.admin` middleware prefix group:
```php
Volt::route('/service-types', 'pages.admin.service-types')->name('service-types');
```

Component features:
- Lists all global service types (name + color swatch preview)
- Inline create form: name field + color picker (8 preset swatches)
- Inline edit name/color
- Delete button with confirmation — pre-checked with `credentials()->exists()` before deletion
- Accessible from admin nav

### UI — Per-Org Service Types Tab

New route inside the existing `org.member` middleware group:
```php
Volt::route('/organizations/{organization}/service-types', 'pages.organizations.service-types')
    ->name('organizations.service-types');
```

Component features:
- New tab in org navigation (alongside Members, Settings) — visible to all members
- Shows global types (labeled "Global", read-only for non-super-admins) + org's custom types
- Owners see an inline "Add custom type" form: name + color swatch picker
- Owners can delete org-specific types (pre-checked: no credentials assigned)
- Super admin can manage both global and org-specific types from this view

### Color Palette (preset 8 swatches)

```
bg-blue-100 text-blue-700
bg-purple-100 text-purple-700
bg-pink-100 text-pink-700
bg-orange-100 text-orange-700
bg-cyan-100 text-cyan-700
bg-emerald-100 text-emerald-700
bg-yellow-100 text-yellow-700
bg-gray-100 text-gray-600
```

### Credential Forms (create/edit)

- `service_type` field → `service_type_id` (integer)
- Dropdown populated from merged global + org types query
- Validation rule: `exists:service_types,id` (replacing `in:hosting,domain,...`)

---

## Files Affected

### New migrations
- `add_slug_to_organizations_table`
- `create_organization_slug_history_table`
- `create_service_types_table`
- `migrate_credentials_service_type_to_fk` (data migration — seeds globals, remaps FKs, drops enum)

### New models
- `app/Models/OrganizationSlugHistory.php`
- `app/Models/ServiceType.php`

### New services
- `app/Services/SlugService.php`

### New exceptions
- `app/Exceptions/OldSlugRedirectException.php`

### New policies
- `app/Policies/ServiceTypePolicy.php`

### Modified files
- `app/Models/Organization.php` — `getRouteKeyName()`, `resolveRouteBinding()`, `slugHistory()`, `serviceTypes()`
- `app/Models/Credential.php` — `service_type_id`, `serviceType()` relationship
- `bootstrap/app.php` — register `OldSlugRedirectException` renderable handler (301 redirect)
- `app/Http/Middleware/VerifyOrganizationMembership.php` — ensure null org (slug not found) returns 404 not 403
- `routes/web.php` — add org service-types route (in `org.member` group) + admin service-types route (in `super.admin` group)
- `resources/views/livewire/pages/organizations/show.blade.php` — dynamic type filter with `service_type_id`
- `resources/views/livewire/pages/organizations/settings.blade.php` — slug update on name change
- `resources/views/livewire/pages/credentials/create.blade.php` — `service_type_id` dropdown
- `resources/views/livewire/pages/credentials/edit.blade.php` — `service_type_id` dropdown

### New Volt components
- `resources/views/livewire/pages/admin/service-types.blade.php`
- `resources/views/livewire/pages/organizations/service-types.blade.php`

---

## Out of Scope

- Slug history UI (old slugs redirect silently, no management page)
- Custom hex colors beyond the 8 preset swatches
- Importing/exporting service types across orgs
- Renaming global service type names from within an org context
