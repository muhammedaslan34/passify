# Passify — Credential Vault Design

**Date:** 2026-03-02
**Stack:** Laravel + Blade + Livewire
**Status:** Approved

---

## Overview

Passify is a multi-organization credential vault. Each website project is its own organization. Users belong to organizations with a role, and can view/manage stored account credentials (email, password, notes, links, etc.) scoped to that org. A Super Admin oversees the entire platform.

---

## Database & Data Model

### `users`
Standard Laravel auth table with one addition:
- `is_super_admin` (boolean, default false)

### `organizations`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | Website/project name |
| website_url | string | nullable |
| description | text | nullable |
| created_by | FK → users | |
| timestamps | | |

### `organization_user` (pivot)
| Column | Type | Notes |
|--------|------|-------|
| organization_id | FK | |
| user_id | FK | |
| role | enum | `owner`, `member` |
| timestamps | | |

### `invitations`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| organization_id | FK | |
| email | string | |
| role | enum | `owner`, `member` |
| token | string | unique, random |
| accepted_at | timestamp | nullable |
| expires_at | timestamp | 7 days from creation |
| timestamps | | |

### `credentials`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| organization_id | FK | |
| service_type | enum | see below |
| name | string | Display name |
| website_url | string | nullable |
| email | string | nullable |
| password | string | plain text |
| note | text | nullable |
| timestamps | | |

**Service type enum:** `hosting`, `domain`, `email`, `database`, `social_media`, `analytics`, `other`

---

## Roles & Access Control

### Super Admin (global)
- Manages all organizations and all users
- Can enter any org and view its credentials
- Set via `is_super_admin` flag on the user record

### Owner (per organization)
- Create, edit, delete credentials
- Invite users by email or add existing users manually
- Promote/demote members, remove members
- Edit or delete the organization

### Member (per organization)
- View credentials (passwords hidden by default, eye icon to reveal)
- Copy email/password to clipboard
- No add/edit/delete access to credentials or members

### Enforcement
- Laravel middleware: org membership check on all org routes
- Policy classes: `OrganizationPolicy`, `CredentialPolicy`
- Blade `@can` directives to conditionally render action buttons

---

## Pages & Navigation

### Auth (Laravel Breeze)
- `/login`, `/register`, `/forgot-password`

### Super Admin Panel
- `/admin` — Dashboard (stats: orgs, users, credentials)
- `/admin/organizations` — All orgs list, enter/delete any org
- `/admin/users` — All users, toggle super admin flag

### Main Application

| Page | URL | Access |
|------|-----|--------|
| My Organizations | `/organizations` | All authenticated users |
| Create Org | `/organizations/create` | All authenticated users |
| Org Dashboard (credentials list) | `/organizations/{id}` | Members + Owners |
| Add Credential | `/organizations/{id}/credentials/create` | Owners only |
| Edit Credential | `/organizations/{id}/credentials/{id}/edit` | Owners only |
| Org Members | `/organizations/{id}/members` | Members + Owners |
| Org Settings | `/organizations/{id}/settings` | Owners only |

### Key Livewire Behaviors
- Password field hidden by default; eye icon toggles visibility
- Copy-to-clipboard icon next to email and password fields
- Filter credentials by service type (tab or dropdown)
- Search credentials by name within an org

---

## Invitation & User Management Flow

### Invite by Email
1. Owner enters email + role on the Members page
2. System creates `invitations` record with unique token (expires 7 days)
3. Invitation email sent with link: `/invitations/{token}/accept`
4. Existing user → added to org immediately on accept
5. New user → redirected to register, auto-joined after registration
6. Expired/used tokens → show error page

### Add Manually
1. Owner searches existing users by name or email
2. Selects user, assigns role
3. User added immediately — no email sent

### Remove Member
- Owner clicks remove; confirmation modal shown before action

### Leave Organization
- Member can leave from the Members page
- Owner cannot leave if they are the sole owner (must transfer ownership or delete org first)

---

## Technology Choices

| Concern | Choice |
|---------|--------|
| Framework | Laravel 11 |
| Frontend | Blade + Livewire 3 |
| Auth scaffolding | Laravel Breeze (Blade stack) |
| Styling | Tailwind CSS |
| Clipboard | Alpine.js (bundled with Livewire) |
| Mail | Laravel Mail (SMTP / Mailtrap for local dev) |
| Database | MySQL |
