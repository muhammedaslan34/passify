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
Generated from `name` via `Str::slug()`. Uniqueness enforced by appending `-2`, `-3`, etc.

**New `organization_slug_history` table:**
```
id               BIGINT UNSIGNED  PK
organization_id  BIGINT UNSIGNED  FK → organizations (cascade delete)
slug             VARCHAR(255)     UNIQUE NOT NULL
created_at       TIMESTAMP
```
Stores every slug an org has ever had, so old URLs can redirect permanently.

### Model

`Organization::resolveRouteBinding()` override:
1. Look up org by `slug` column — return if found.
2. If not found, check `organization_slug_history` for a matching slug.
3. If history match found — issue a 301 redirect to `route('organizations.show', $org)` with the current slug.
4. If neither found — abort 404.

`Organization` gets a `slugHistory()` hasMany relationship to `OrganizationSlugHistory`.

### Slug Generation

A private `generateUniqueSlug(string $name, ?int $excludeId = null): string` method (or a dedicated `SlugService`) handles:
- `Str::slug($name)` as base
- Appends `-2`, `-3`, etc. until unique (excluding current org's own slug on update)

### Name Change Flow (Settings page)

When org name is saved in `organizations/settings.blade.php`:
1. Generate new slug from new name.
2. If new slug === current slug → no slug change needed.
3. If different → insert current slug into `organization_slug_history` → update `organizations.slug` to new slug.

### Routes

No route changes. All existing `{organization}` route parameters continue to work — Laravel resolves via the overridden `resolveRouteBinding()`.

URLs in views that use `route('organizations.show', $organization)` automatically use the current slug because the model's route key is `slug`.

**`getRouteKeyName()` returns `'slug'`.**

### Migration Strategy

A migration adds the `slug` column and populates it for all existing orgs:
```php
Organization::each(fn($org) => $org->update(['slug' => generateUniqueSlug($org->name)]));
```

---

## Feature 2: Service Types

### Database

**New `service_types` table:**
```
id               BIGINT UNSIGNED  PK
name             VARCHAR(255)     NOT NULL
color            VARCHAR(255)     NOT NULL  -- Tailwind badge class pair, e.g. "bg-blue-100 text-blue-700"
organization_id  BIGINT UNSIGNED  NULLABLE  FK → organizations (cascade delete)
                                            -- NULL = global type (super admin managed)
                                            -- set  = org-specific custom type
created_by       BIGINT UNSIGNED  FK → users (set null on delete)
timestamps
INDEX(organization_id)
```

**`credentials` table — changes:**
- Drop `service_type` enum column
- Add `service_type_id  BIGINT UNSIGNED  FK → service_types (restrict)`

### Data Migration

A migration after schema changes:
1. Seed 7 global service types (organization_id = null):
   - hosting → `bg-blue-100 text-blue-700`
   - domain → `bg-purple-100 text-purple-700`
   - email → `bg-pink-100 text-pink-700`
   - database → `bg-orange-100 text-orange-700`
   - social_media → `bg-cyan-100 text-cyan-700`
   - analytics → `bg-emerald-100 text-emerald-700`
   - other → `bg-gray-100 text-gray-600`
2. Map all existing `credentials.service_type` string values to the new `service_type_id` FK.
3. Drop `credentials.service_type` enum column.

### Models

**`ServiceType` model:**
- `fillable`: `name`, `color`, `organization_id`, `created_by`
- `organization()` belongsTo (nullable)
- `credentials()` hasMany
- Scope `global()`: `whereNull('organization_id')`
- Scope `forOrg(int $orgId)`: `where('organization_id', $orgId)`

**`Credential` model:**
- Replace `service_type` string cast with `service_type_id`
- Add `serviceType()` belongsTo `ServiceType`

**`Organization` model:**
- Add `serviceTypes()` hasMany `ServiceType`

### Service Type Resolution

When populating the type dropdown or filter buttons for an org, query:
```php
ServiceType::where(fn($q) => $q->whereNull('organization_id')->orWhere('organization_id', $org->id))
    ->orderByRaw('organization_id IS NULL DESC')  // globals first
    ->orderBy('name')
    ->get();
```

### Authorization

**Global types** (`organization_id = null`):
- Create / Edit / Delete: super admin only
- View: everyone

**Org-specific types** (`organization_id = $org->id`):
- Create: org owner OR super admin
- Edit / Delete: org owner OR super admin
- View: all org members

A `ServiceTypePolicy` handles these rules.

### UI — Admin Panel

New route: `GET /admin/service-types` → `pages.admin.service-types` Volt component
- Lists all global service types with name + color preview
- Inline create form (name + color picker from preset palette)
- Delete button (with confirmation) — blocked if any credentials reference the type
- Edit name/color inline

Added as a nav link in the admin sidebar/header.

### UI — Per-Org Service Types Tab

New route: `GET /organizations/{organization}/service-types` → `pages.organizations.service-types` Volt component
- Tab added to org navigation (alongside Members, Settings)
- Shows list: global types (labeled "Global", read-only for owners) + org custom types
- Owners see inline "Add custom type" form: name field + color picker (8 preset Tailwind pairs)
- Owners can delete their org's custom types (with confirmation, blocked if referenced by credentials)
- Super admin can manage both global and org-specific types from this view

### Color Palette (preset)

8 options shown as clickable swatches:
```
bg-blue-100 text-blue-700      (blue)
bg-purple-100 text-purple-700  (purple)
bg-pink-100 text-pink-700      (pink)
bg-orange-100 text-orange-700  (orange)
bg-cyan-100 text-cyan-700      (cyan)
bg-emerald-100 text-emerald-700 (emerald)
bg-yellow-100 text-yellow-700  (yellow)
bg-gray-100 text-gray-600      (gray)
```

### Credential Forms (create/edit)

`service_type` select becomes a dropdown populated from the merged global + org types query. Stores `service_type_id` instead of enum string.

Validation rule changes from `in:hosting,domain,...` to `exists:service_types,id`.

### Organization Show Page (filter bar)

Filter buttons dynamically generated from the merged type list instead of hardcoded array. `serviceTypeBadge()` and `serviceTypeLabel()` helpers replaced by reading from the `ServiceType` model's `color` and `name` fields.

---

## Files Affected

### New migrations
- `add_slug_to_organizations_table`
- `create_organization_slug_history_table`
- `create_service_types_table`
- `migrate_credentials_service_type_to_fk` (data migration)

### New models
- `app/Models/OrganizationSlugHistory.php`
- `app/Models/ServiceType.php`

### New policies
- `app/Policies/ServiceTypePolicy.php`

### Modified models
- `app/Models/Organization.php` — slug logic, resolveRouteBinding, slugHistory()
- `app/Models/Credential.php` — service_type_id, serviceType()

### New Volt components
- `resources/views/livewire/pages/admin/service-types.blade.php`
- `resources/views/livewire/pages/organizations/service-types.blade.php`

### Modified Volt components
- `pages/organizations/show.blade.php` — dynamic type filter
- `pages/organizations/settings.blade.php` — slug update on name change
- `pages/credentials/create.blade.php` — service_type_id dropdown
- `pages/credentials/edit.blade.php` — service_type_id dropdown

### Modified routes
- `routes/web.php` — add org service-types route, admin service-types route

---

## Out of Scope

- Slug history UI (no page to view old slugs — they just redirect silently)
- Custom colors beyond the 8 preset swatches
- Importing/exporting service types across orgs
