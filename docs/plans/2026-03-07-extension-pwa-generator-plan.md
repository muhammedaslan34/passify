# Chrome Extension, PWA & Password Generator Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a Chrome extension (auto-save/autofill credentials, password generator), a mobile PWA (full CRUD), and a shared password generator module to Passify.

**Architecture:** A new Laravel Sanctum REST API at `/api/` serves both the Chrome extension (Manifest V3) and the PWA (Vue 3 + Vite in `/pwa/`). A shared zero-dependency `password-generator.js` module is used across the web app (via Alpine.js modal), extension popup, and PWA.

**Tech Stack:** Laravel 12 + Laravel Sanctum, Manifest V3 (vanilla JS), Vue 3 + Vite (PWA), Tailwind CSS, Alpine.js (web app modal), PHPUnit (API tests)

---

## Task 1: Install and Configure Laravel Sanctum

**Files:**
- Modify: `config/auth.php`
- Modify: `bootstrap/app.php`
- Create: `routes/api.php`
- Modify: `app/Models/User.php`

**Step 1: Install Sanctum**

```bash
cd F:/Projects/laravel/passify
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

Expected: `personal_access_tokens` table created in DB.

**Step 2: Add HasApiTokens to User model**

Open `app/Models/User.php`. Add to the `use` block at top:
```php
use Laravel\Sanctum\HasApiTokens;
```
Add `HasApiTokens` to the class `use` statement:
```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ...existing code...
}
```

**Step 3: Register api.php routes in bootstrap/app.php**

Open `bootstrap/app.php`. Inside `->withRouting(...)`, add:
```php
api: __DIR__.'/../routes/api.php',
apiPrefix: 'api',
```

**Step 4: Create routes/api.php**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [\App\Http\Controllers\Api\AuthController::class, 'token']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/auth/token', [\App\Http\Controllers\Api\AuthController::class, 'revoke']);
    Route::get('/organizations', [\App\Http\Controllers\Api\OrganizationController::class, 'index']);
    Route::get('/organizations/{organization}', [\App\Http\Controllers\Api\OrganizationController::class, 'show']);
    Route::get('/organizations/{organization}/credentials', [\App\Http\Controllers\Api\CredentialController::class, 'index']);
    Route::post('/organizations/{organization}/credentials', [\App\Http\Controllers\Api\CredentialController::class, 'store']);
    Route::put('/organizations/{organization}/credentials/{credential}', [\App\Http\Controllers\Api\CredentialController::class, 'update']);
    Route::delete('/organizations/{organization}/credentials/{credential}', [\App\Http\Controllers\Api\CredentialController::class, 'destroy']);
    Route::get('/credentials/search', [\App\Http\Controllers\Api\CredentialController::class, 'search']);
});
```

**Step 5: Configure CORS in config/cors.php**

Open `config/cors.php`. Set:
```php
'paths' => ['api/*'],
'allowed_origins' => ['*'],   // tighten to extension ID in production
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,
```

**Step 6: Commit**

```bash
git add composer.json composer.lock config/sanctum.php config/cors.php database/migrations routes/api.php bootstrap/app.php app/Models/User.php
git commit -m "feat: install Laravel Sanctum and scaffold API routes"
```

---

## Task 2: AuthController (API login/logout)

**Files:**
- Create: `app/Http/Controllers/Api/AuthController.php`
- Create: `tests/Feature/Api/AuthTest.php`

**Step 1: Write the failing tests**

Create `tests/Feature/Api/AuthTest.php`:
```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_token_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'chrome-extension',
        ]);

        $response->assertOk()->assertJsonStructure(['token']);
    }

    public function test_invalid_credentials_return_422(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->postJson('/api/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'test',
        ])->assertUnprocessable();
    }

    public function test_user_can_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/auth/token')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/organizations')
            ->assertUnauthorized();
    }
}
```

**Step 2: Run tests to see them fail**

```bash
php artisan test tests/Feature/Api/AuthTest.php
```
Expected: FAIL — `AuthController` not found.

**Step 3: Create AuthController**

Create `app/Http/Controllers/Api/AuthController.php`:
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function token(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken($request->device_name)->plainTextToken,
        ]);
    }

    public function revoke(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Token revoked.']);
    }
}
```

**Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Api/AuthTest.php
```
Expected: 3 tests PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Api/AuthController.php tests/Feature/Api/AuthTest.php
git commit -m "feat: add API auth controller with token login and revoke"
```

---

## Task 3: OrganizationController (API)

**Files:**
- Create: `app/Http/Controllers/Api/OrganizationController.php`
- Create: `tests/Feature/Api/OrganizationApiTest.php`

**Step 1: Write the failing tests**

Create `tests/Feature/Api/OrganizationApiTest.php`:
```php
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
        Organization::factory()->create(); // another org the user doesn't belong to

        $this->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $myOrg->id);
    }

    public function test_show_returns_org_with_credentials_count(): void
    {
        $user = $this->actingAsApiUser();
        $org = Organization::factory()->create(['created_by' => $user->id]);
        $org->members()->attach($user->id, ['role' => 'owner']);

        $this->getJson("/api/organizations/{$org->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $org->id)
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
}
```

**Step 2: Create Organization factory if not exists**

Check `database/factories/OrganizationFactory.php`. If missing, create it:
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'website_url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
```

Also add `use HasFactory;` to `Organization` model and `use Database\Factories\OrganizationFactory;` if needed.

**Step 3: Run tests to see them fail**

```bash
php artisan test tests/Feature/Api/OrganizationApiTest.php
```

**Step 4: Create OrganizationController**

Create `app/Http/Controllers/Api/OrganizationController.php`:
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $orgs = $request->user()
            ->organizations()
            ->withCount('credentials')
            ->get()
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'website_url' => $org->website_url,
                'description' => $org->description,
                'role' => $org->pivot->role,
                'credentials_count' => $org->credentials_count,
            ]);

        return response()->json(['data' => $orgs]);
    }

    public function show(Request $request, Organization $organization)
    {
        if (! $organization->isMemberOf($request->user())) {
            abort(403);
        }

        $role = $organization->members()
            ->where('user_id', $request->user()->id)
            ->first()->pivot->role;

        return response()->json(['data' => [
            'id' => $organization->id,
            'name' => $organization->name,
            'website_url' => $organization->website_url,
            'description' => $organization->description,
            'role' => $role,
            'credentials_count' => $organization->credentials()->count(),
        ]]);
    }
}
```

**Step 5: Run tests**

```bash
php artisan test tests/Feature/Api/OrganizationApiTest.php
```
Expected: 4 tests PASS.

**Step 6: Commit**

```bash
git add app/Http/Controllers/Api/OrganizationController.php tests/Feature/Api/OrganizationApiTest.php database/factories/OrganizationFactory.php
git commit -m "feat: add API organization controller with membership enforcement"
```

---

## Task 4: CredentialController (API)

**Files:**
- Create: `app/Http/Controllers/Api/CredentialController.php`
- Create: `tests/Feature/Api/CredentialApiTest.php`
- Create: `database/factories/CredentialFactory.php`

**Step 1: Write the failing tests**

Create `tests/Feature/Api/CredentialApiTest.php`:
```php
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
        return [$user, $org, $token];
    }

    public function test_owner_can_list_credentials(): void
    {
        [$user, $org, $token] = $this->setupOwner();
        Credential::factory()->count(3)->create(['organization_id' => $org->id]);

        $this->withToken($token)
            ->getJson("/api/organizations/{$org->id}/credentials")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_owner_can_create_credential(): void
    {
        [$user, $org, $token] = $this->setupOwner();

        $this->withToken($token)
            ->postJson("/api/organizations/{$org->id}/credentials", [
                'service_type' => 'hosting',
                'name' => 'cPanel',
                'email' => 'admin@example.com',
                'password' => 'secret',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'cPanel');

        $this->assertDatabaseHas('credentials', ['name' => 'cPanel']);
    }

    public function test_owner_can_update_credential(): void
    {
        [$user, $org, $token] = $this->setupOwner();
        $cred = Credential::factory()->create(['organization_id' => $org->id]);

        $this->withToken($token)
            ->putJson("/api/organizations/{$org->id}/credentials/{$cred->id}", [
                'name' => 'Updated Name',
                'password' => 'newpassword',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_owner_can_delete_credential(): void
    {
        [$user, $org, $token] = $this->setupOwner();
        $cred = Credential::factory()->create(['organization_id' => $org->id]);

        $this->withToken($token)
            ->deleteJson("/api/organizations/{$org->id}/credentials/{$cred->id}")
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
        $token = $member->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/organizations/{$org->id}/credentials", [
                'service_type' => 'other', 'name' => 'X', 'password' => 'y',
            ])
            ->assertForbidden();
    }

    public function test_search_returns_credentials_matching_url(): void
    {
        [$user, $org, $token] = $this->setupOwner();
        Credential::factory()->create([
            'organization_id' => $org->id,
            'website_url' => 'https://cpanel.example.com',
            'name' => 'cPanel',
        ]);

        $this->withToken($token)
            ->getJson('/api/credentials/search?url=cpanel.example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
```

**Step 2: Create CredentialFactory**

Create `database/factories/CredentialFactory.php`:
```php
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
            'service_type' => $this->faker->randomElement(['hosting', 'domain', 'email', 'database', 'social_media', 'analytics', 'other']),
            'name' => $this->faker->company(),
            'website_url' => $this->faker->url(),
            'email' => $this->faker->email(),
            'password' => $this->faker->password(),
            'note' => $this->faker->sentence(),
        ];
    }
}
```

Add `use HasFactory;` to `Credential` model.

**Step 3: Run tests to see them fail**

```bash
php artisan test tests/Feature/Api/CredentialApiTest.php
```

**Step 4: Create CredentialController**

Create `app/Http/Controllers/Api/CredentialController.php`:
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Organization;
use Illuminate\Http\Request;

class CredentialController extends Controller
{
    public function index(Request $request, Organization $organization)
    {
        if (! $organization->isMemberOf($request->user())) {
            abort(403);
        }

        $credentials = $organization->credentials()
            ->get()
            ->map(fn ($c) => $this->format($c));

        return response()->json(['data' => $credentials]);
    }

    public function store(Request $request, Organization $organization)
    {
        if (! $organization->isOwner($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'service_type' => ['required', 'in:hosting,domain,email,database,social_media,analytics,other'],
            'name'         => ['required', 'string', 'max:255'],
            'website_url'  => ['nullable', 'url', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'password'     => ['required', 'string', 'max:1000'],
            'note'         => ['nullable', 'string', 'max:2000'],
        ]);

        $credential = $organization->credentials()->create($validated);

        return response()->json(['data' => $this->format($credential)], 201);
    }

    public function update(Request $request, Organization $organization, Credential $credential)
    {
        if (! $organization->isOwner($request->user())) {
            abort(403);
        }

        abort_if($credential->organization_id !== $organization->id, 404);

        $validated = $request->validate([
            'service_type' => ['sometimes', 'in:hosting,domain,email,database,social_media,analytics,other'],
            'name'         => ['sometimes', 'string', 'max:255'],
            'website_url'  => ['nullable', 'url', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'password'     => ['sometimes', 'string', 'max:1000'],
            'note'         => ['nullable', 'string', 'max:2000'],
        ]);

        $credential->update($validated);

        return response()->json(['data' => $this->format($credential->fresh())]);
    }

    public function destroy(Request $request, Organization $organization, Credential $credential)
    {
        if (! $organization->isOwner($request->user())) {
            abort(403);
        }

        abort_if($credential->organization_id !== $organization->id, 404);

        $credential->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function search(Request $request)
    {
        $user = $request->user();
        $url = $request->query('url', '');
        $q = $request->query('q', '');

        $orgIds = $user->organizations()->pluck('organizations.id');

        $credentials = Credential::whereIn('organization_id', $orgIds)
            ->when($url, fn ($query) => $query->where('website_url', 'like', "%{$url}%"))
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->with('organization:id,name')
            ->limit(10)
            ->get()
            ->map(fn ($c) => array_merge($this->format($c), [
                'organization_name' => $c->organization->name,
            ]));

        return response()->json(['data' => $credentials]);
    }

    private function format(Credential $c): array
    {
        return [
            'id' => $c->id,
            'organization_id' => $c->organization_id,
            'service_type' => $c->service_type,
            'name' => $c->name,
            'website_url' => $c->website_url,
            'email' => $c->email,
            'password' => $c->password,
            'note' => $c->note,
        ];
    }
}
```

**Step 5: Run tests**

```bash
php artisan test tests/Feature/Api/CredentialApiTest.php
```
Expected: 6 tests PASS.

**Step 6: Run all API tests together**

```bash
php artisan test tests/Feature/Api/
```
Expected: all PASS.

**Step 7: Commit**

```bash
git add app/Http/Controllers/Api/CredentialController.php tests/Feature/Api/CredentialApiTest.php database/factories/CredentialFactory.php database/factories/OrganizationFactory.php app/Models/Credential.php app/Models/Organization.php
git commit -m "feat: add API credential controller with CRUD and URL search"
```

---

## Task 5: Extension Auth Route (OAuth-style redirect)

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/ExtensionAuthController.php`
- Create: `resources/views/extension-auth.blade.php`

**Step 1: Create the controller**

Create `app/Http/Controllers/ExtensionAuthController.php`:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExtensionAuthController extends Controller
{
    // Shows a simple "Connect Extension" page — user must be logged in
    public function show(Request $request)
    {
        return view('extension-auth');
    }

    // Generates token and redirects to the extension custom scheme
    public function connect(Request $request)
    {
        $token = $request->user()->createToken('chrome-extension')->plainTextToken;
        // The extension's background script intercepts this redirect
        return redirect('passify-extension://auth?token=' . urlencode($token));
    }
}
```

**Step 2: Add routes to web.php**

Add to `routes/web.php` inside the `auth+verified` middleware group:
```php
Route::get('/extension/auth', [\App\Http\Controllers\ExtensionAuthController::class, 'show'])
    ->name('extension.auth');
Route::post('/extension/auth/connect', [\App\Http\Controllers\ExtensionAuthController::class, 'connect'])
    ->name('extension.connect');
```

**Step 3: Create the blade view**

Create `resources/views/extension-auth.blade.php`:
```html
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Connect Chrome Extension</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-sm mx-auto px-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Connect Passify Extension</h3>
                <p class="text-sm text-gray-500 mb-6">Click the button below to grant the extension access to your Passify account. A secure token will be generated and sent to the extension.</p>
                <form method="POST" action="{{ route('extension.connect') }}">
                    @csrf
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition">
                        Connect Extension
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
```

**Step 4: Manual test**

```
1. Run: php artisan serve
2. Login at http://localhost:8000
3. Visit http://localhost:8000/extension/auth
4. Click "Connect Extension"
5. Observe redirect to passify-extension://auth?token=... (browser shows "can't open page" — that's expected, extension isn't installed yet)
```

**Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/ExtensionAuthController.php resources/views/extension-auth.blade.php
git commit -m "feat: add extension OAuth-style auth route for token handoff"
```

---

## Task 6: Password Generator JS Module

**Files:**
- Create: `resources/js/password-generator.js`

**Step 1: Create the module**

Create `resources/js/password-generator.js`:
```js
const CHARS = {
    lowercase: 'abcdefghijklmnopqrstuvwxyz',
    uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    numbers: '0123456789',
    symbols: '!@#$%^&*()_+-=[]{}|;:,.<>?',
};

/**
 * Generate a random password.
 * @param {object} opts
 * @param {number} opts.length
 * @param {boolean} opts.lowercase
 * @param {boolean} opts.uppercase
 * @param {boolean} opts.numbers
 * @param {boolean} opts.symbols
 * @returns {string}
 */
export function generate({ length = 16, lowercase = true, uppercase = true, numbers = true, symbols = true } = {}) {
    let pool = '';
    const required = [];

    if (lowercase) { pool += CHARS.lowercase; required.push(randomChar(CHARS.lowercase)); }
    if (uppercase) { pool += CHARS.uppercase; required.push(randomChar(CHARS.uppercase)); }
    if (numbers)   { pool += CHARS.numbers;   required.push(randomChar(CHARS.numbers)); }
    if (symbols)   { pool += CHARS.symbols;   required.push(randomChar(CHARS.symbols)); }

    if (!pool) return '';

    const remaining = Array.from({ length: length - required.length }, () => randomChar(pool));
    const all = [...required, ...remaining];

    // Shuffle using crypto random
    for (let i = all.length - 1; i > 0; i--) {
        const j = Math.floor(getCryptoRandom() * (i + 1));
        [all[i], all[j]] = [all[j], all[i]];
    }

    return all.join('');
}

/**
 * Score password strength.
 * @param {string} password
 * @returns {{ score: number, label: string, color: string, percent: number }}
 */
export function strength(password) {
    if (!password) return { score: 0, label: 'None', color: '#e5e7eb', percent: 0 };

    let score = 0;
    if (password.length >= 8)  score++;
    if (password.length >= 12) score++;
    if (password.length >= 16) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;

    const clamped = Math.min(4, Math.floor(score * 4 / 6));

    const levels = [
        { label: 'Very Weak', color: '#ef4444' },
        { label: 'Weak',      color: '#f97316' },
        { label: 'Fair',      color: '#eab308' },
        { label: 'Strong',    color: '#22c55e' },
        { label: 'Very Strong', color: '#16a34a' },
    ];

    return { score: clamped, percent: ((clamped + 1) / 5) * 100, ...levels[clamped] };
}

function randomChar(str) {
    return str[Math.floor(getCryptoRandom() * str.length)];
}

function getCryptoRandom() {
    return crypto.getRandomValues(new Uint32Array(1))[0] / (0xFFFFFFFF + 1);
}
```

**Step 2: Commit**

```bash
git add resources/js/password-generator.js
git commit -m "feat: add shared password-generator.js module with strength scoring"
```

---

## Task 7: Password Generator Modal in Web App (Livewire)

**Files:**
- Create: `resources/views/components/password-generator-modal.blade.php`
- Modify: `resources/views/livewire/pages/credentials/create.blade.php`
- Modify: `resources/views/livewire/pages/credentials/edit.blade.php`
- Modify: `resources/js/app.js`

**Step 1: Register password-generator in app.js**

Open `resources/js/app.js`, add at the bottom:
```js
import { generate, strength } from './password-generator.js';
window.passifyGenerator = { generate, strength };
```

**Step 2: Create the modal component**

Create `resources/views/components/password-generator-modal.blade.php`:
```html
{{-- Usage: <x-password-generator-modal target="password" /> --}}
@props(['target' => 'password'])

<div
    x-data="{
        open: false,
        length: 16,
        lowercase: true,
        uppercase: true,
        numbers: true,
        symbols: true,
        generated: '',
        strengthLabel: '',
        strengthColor: '#e5e7eb',
        strengthPercent: 0,
        generate() {
            this.generated = window.passifyGenerator.generate({
                length: this.length,
                lowercase: this.lowercase,
                uppercase: this.uppercase,
                numbers: this.numbers,
                symbols: this.symbols,
            });
            const s = window.passifyGenerator.strength(this.generated);
            this.strengthLabel = s.label;
            this.strengthColor = s.color;
            this.strengthPercent = s.percent;
        },
        use() {
            this.$dispatch('use-generated-password', { password: this.generated });
            this.open = false;
        },
        copy() {
            navigator.clipboard.writeText(this.generated);
        },
    }"
    x-init="generate()"
>
    <button type="button" @click="open = true"
        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
        Generate password
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="open = false">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h3 class="text-base font-semibold text-gray-900 mb-4">Password Generator</h3>

            {{-- Strength bar --}}
            <div class="mb-4">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-500">Strength</span>
                    <span class="font-medium" x-bind:style="'color:' + strengthColor" x-text="strengthLabel"></span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300"
                         x-bind:style="'width:' + strengthPercent + '%; background:' + strengthColor"></div>
                </div>
            </div>

            {{-- Length slider --}}
            <div class="mb-4">
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>Password length</span>
                    <span class="font-mono font-semibold" x-text="length + ' characters'"></span>
                </div>
                <input type="range" min="8" max="64" x-model.number="length" @input="generate()"
                    class="w-full accent-indigo-600">
            </div>

            {{-- Options --}}
            <div class="space-y-2 mb-4 text-sm text-gray-700">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="lowercase" @change="generate()" class="accent-indigo-600">
                    Lowercase (abc)
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="uppercase" @change="generate()" class="accent-indigo-600">
                    Uppercase (ABC)
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="numbers" @change="generate()" class="accent-indigo-600">
                    Numbers (123)
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="symbols" @change="generate()" class="accent-indigo-600">
                    Randomized symbols (!#$)
                </label>
            </div>

            {{-- Generated password --}}
            <div class="flex items-center gap-2 mb-4 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                <span class="flex-1 font-mono text-sm text-gray-800 break-all" x-text="generated"></span>
                <button type="button" @click="copy()" title="Copy" class="text-gray-400 hover:text-indigo-600 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="generate()"
                    class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2 text-sm font-medium hover:bg-gray-50 transition">
                    Regenerate
                </button>
                <button type="button" @click="use()"
                    class="flex-1 bg-indigo-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-indigo-700 transition">
                    Use Password
                </button>
            </div>
        </div>
    </div>
</div>
```

**Step 3: Add the modal to credentials/create.blade.php**

In `credentials/create.blade.php`, find the password field `<div x-data="{ show: false }">` block. Replace it with:
```html
<div x-data="{ show: false }" @use-generated-password.window="password = $event.detail.password; $wire.set('password', $event.detail.password)">
    <div class="flex items-center justify-between">
        <x-input-label for="password" value="Password *"/>
        <x-password-generator-modal />
    </div>
    <div class="relative mt-1">
        <x-text-input wire:model="password" id="password"
                      x-bind:type="show ? 'text' : 'password'"
                      class="block w-full pr-10 font-mono" placeholder="Password" required/>
        <button type="button" x-on:click="show = !show"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-500 transition">
            <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
        </button>
    </div>
    <x-input-error :messages="$errors->get('password')" class="mt-2"/>
</div>
```

Apply the same change to `credentials/edit.blade.php`.

**Step 4: Build assets and manual test**

```bash
npm run build
php artisan serve
```

Visit `/organizations/{id}/credentials/create`, verify "Generate password" link appears, modal opens, generator works, "Use Password" fills the field.

**Step 5: Commit**

```bash
git add resources/js/app.js resources/views/components/password-generator-modal.blade.php resources/views/livewire/pages/credentials/create.blade.php resources/views/livewire/pages/credentials/edit.blade.php
git commit -m "feat: add password generator modal to credential create/edit forms"
```

---

## Task 8: Chrome Extension Scaffold

**Files:**
- Create: `chrome-extension/manifest.json`
- Create: `chrome-extension/background/background.js`
- Create: `chrome-extension/popup/popup.html`
- Create: `chrome-extension/popup/popup.js`
- Create: `chrome-extension/popup/popup.css`
- Create: `chrome-extension/content/content.js`
- Copy: `resources/js/password-generator.js` → `chrome-extension/shared/password-generator.js`

**Step 1: Copy the shared generator module**

```bash
mkdir -p chrome-extension/shared chrome-extension/popup chrome-extension/content chrome-extension/background
cp resources/js/password-generator.js chrome-extension/shared/password-generator.js
```

Edit the copy to use CommonJS exports instead of ES module export (for Manifest V3 service worker compatibility). Change the last lines to:
```js
// At top: remove "export" keywords, add at bottom:
if (typeof module !== 'undefined') {
    module.exports = { generate, strength };
} else {
    self.passifyGenerator = { generate, strength };
}
```

**Step 2: Create manifest.json**

Create `chrome-extension/manifest.json`:
```json
{
    "manifest_version": 3,
    "name": "Passify",
    "version": "1.0.0",
    "description": "Auto-save and autofill credentials from your Passify vault.",
    "permissions": ["storage", "activeTab", "tabs", "scripting"],
    "host_permissions": ["https://*/*", "http://*/*"],
    "background": {
        "service_worker": "background/background.js",
        "type": "module"
    },
    "action": {
        "default_popup": "popup/popup.html",
        "default_title": "Passify",
        "default_icon": {
            "16": "icons/icon16.png",
            "48": "icons/icon48.png",
            "128": "icons/icon128.png"
        }
    },
    "content_scripts": [
        {
            "matches": ["https://*/*", "http://*/*"],
            "js": ["content/content.js"],
            "run_at": "document_idle"
        }
    ],
    "icons": {
        "16": "icons/icon16.png",
        "48": "icons/icon48.png",
        "128": "icons/icon128.png"
    }
}
```

Create placeholder icon directory (add real icons later):
```bash
mkdir -p chrome-extension/icons
```

**Step 3: Create background service worker**

Create `chrome-extension/background/background.js`:
```js
const PASSIFY_URL = 'http://localhost:8000'; // Change to production URL

// Listen for OAuth redirect from extension auth page
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
    if (changeInfo.url && changeInfo.url.startsWith('passify-extension://auth')) {
        const url = new URL(changeInfo.url.replace('passify-extension://', 'https://passify-extension/'));
        const token = url.searchParams.get('token');
        if (token) {
            chrome.storage.local.set({ token, passifyUrl: PASSIFY_URL }, () => {
                chrome.tabs.remove(tabId);
                chrome.action.setBadgeText({ text: '' });
            });
        }
    }
});

// Proxy API calls from content scripts (avoids CORS issues in content scripts)
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message.type === 'API_REQUEST') {
        chrome.storage.local.get(['token', 'passifyUrl'], ({ token, passifyUrl }) => {
            const base = passifyUrl || PASSIFY_URL;
            fetch(`${base}/api${message.path}`, {
                method: message.method || 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: message.body ? JSON.stringify(message.body) : undefined,
            })
            .then(r => r.json())
            .then(data => sendResponse({ ok: true, data }))
            .catch(err => sendResponse({ ok: false, error: err.message }));
        });
        return true; // keep message channel open for async response
    }
});
```

**Step 4: Create content script**

Create `chrome-extension/content/content.js`:
```js
(function () {
    'use strict';

    let saveBarShown = false;
    let fillBarShown = false;

    // ── Autofill: check if current URL has saved credentials ──────────────
    function checkAutofill() {
        const currentUrl = window.location.hostname;
        chrome.runtime.sendMessage(
            { type: 'API_REQUEST', path: `/credentials/search?url=${encodeURIComponent(currentUrl)}`, method: 'GET' },
            (response) => {
                if (response?.ok && response.data?.data?.length > 0 && !fillBarShown) {
                    showAutofillBanner(response.data.data);
                }
            }
        );
    }

    function showAutofillBanner(credentials) {
        fillBarShown = true;
        const bar = document.createElement('div');
        bar.id = 'passify-autofill-bar';
        bar.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:2147483647;background:#4f46e5;color:white;padding:10px 16px;display:flex;align-items:center;gap:12px;font-family:system-ui,sans-serif;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.2)';

        const cred = credentials[0]; // Use first match
        bar.innerHTML = `
            <span style="flex:1">Passify: Fill <strong>${cred.name}</strong> (${cred.email || 'no email'})?</span>
            <button id="passify-fill-btn" style="background:white;color:#4f46e5;border:none;border-radius:6px;padding:4px 12px;cursor:pointer;font-weight:600;font-size:12px">Autofill</button>
            <button id="passify-fill-dismiss" style="background:transparent;border:none;color:white;cursor:pointer;font-size:16px;line-height:1">✕</button>
        `;
        document.body.prepend(bar);

        document.getElementById('passify-fill-btn').onclick = () => {
            fillCredential(cred);
            bar.remove();
        };
        document.getElementById('passify-fill-dismiss').onclick = () => bar.remove();
    }

    function fillCredential(cred) {
        const emailInput = document.querySelector('input[type="email"], input[name*="email"], input[name*="user"], input[name*="login"]');
        const passwordInput = document.querySelector('input[type="password"]');
        if (emailInput && cred.email) {
            emailInput.value = cred.email;
            emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (passwordInput) {
            passwordInput.value = cred.password;
            passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    // ── Save: intercept form submit ────────────────────────────────────────
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const passwordInput = form.querySelector('input[type="password"]');
        const emailInput = form.querySelector('input[type="email"], input[name*="email"], input[name*="user"]');
        if (!passwordInput || saveBarShown) return;

        const password = passwordInput.value;
        const email = emailInput?.value || '';
        if (!password) return;

        e.preventDefault();
        showSaveBanner(email, password, () => form.submit());
    }, true);

    function showSaveBanner(email, password, submitCallback) {
        saveBarShown = true;
        const bar = document.createElement('div');
        bar.id = 'passify-save-bar';
        bar.style.cssText = 'position:fixed;bottom:0;left:0;right:0;z-index:2147483647;background:#1e1b4b;color:white;padding:12px 16px;display:flex;align-items:center;gap:12px;font-family:system-ui,sans-serif;font-size:13px;box-shadow:0 -2px 8px rgba(0,0,0,.2)';
        bar.innerHTML = `
            <span style="flex:1">Save <strong>${window.location.hostname}</strong> credentials to Passify?</span>
            <button id="passify-save-btn" style="background:#4f46e5;color:white;border:none;border-radius:6px;padding:4px 12px;cursor:pointer;font-weight:600;font-size:12px">Save</button>
            <button id="passify-save-dismiss" style="background:transparent;border:none;color:#a5b4fc;cursor:pointer;font-size:12px">Not now</button>
        `;
        document.body.appendChild(bar);

        document.getElementById('passify-save-btn').onclick = () => {
            bar.remove();
            chrome.runtime.sendMessage({ type: 'SAVE_CREDENTIAL', email, password, url: window.location.href, hostname: window.location.hostname });
            submitCallback();
        };
        document.getElementById('passify-save-dismiss').onclick = () => {
            bar.remove();
            submitCallback();
        };
    }

    // Run autofill check after a short delay
    setTimeout(checkAutofill, 1500);
})();
```

**Step 5: Create popup HTML**

Create `chrome-extension/popup/popup.html`:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passify</title>
    <link rel="stylesheet" href="popup.css">
</head>
<body>
    <div class="header">
        <img src="../icons/icon48.png" alt="Passify" class="logo" onerror="this.style.display='none'">
        <span class="title">Passify</span>
        <div id="auth-status" class="status"></div>
    </div>

    <div class="tabs">
        <button class="tab active" data-tab="generator">Generator</button>
        <button class="tab" data-tab="search">Search</button>
        <button class="tab" data-tab="settings">Settings</button>
    </div>

    <!-- Generator tab -->
    <div id="tab-generator" class="tab-content active">
        <div class="strength-bar-wrapper">
            <div class="strength-label">
                <span>Strength</span>
                <span id="strength-text" class="strength-text">Very Strong</span>
            </div>
            <div class="strength-track">
                <div id="strength-fill" class="strength-fill"></div>
            </div>
        </div>
        <div class="length-wrapper">
            <div class="length-label">
                <span>Password length</span>
                <span id="length-display">16 characters</span>
            </div>
            <input type="range" id="length-slider" min="8" max="64" value="16">
        </div>
        <div class="options">
            <label><input type="checkbox" id="opt-lowercase" checked> Lowercase (abc)</label>
            <label><input type="checkbox" id="opt-uppercase" checked> Uppercase (ABC)</label>
            <label><input type="checkbox" id="opt-numbers" checked> Numbers (123)</label>
            <label><input type="checkbox" id="opt-symbols" checked> Randomized symbols (!#$)</label>
        </div>
        <div class="password-display">
            <span id="generated-password" class="password-text"></span>
            <button id="copy-btn" class="icon-btn" title="Copy">⧉</button>
        </div>
        <button id="generate-btn" class="btn-primary">Generate</button>
    </div>

    <!-- Search tab -->
    <div id="tab-search" class="tab-content">
        <input type="text" id="search-input" placeholder="Search by name or URL..." class="search-input">
        <div id="search-results" class="search-results"></div>
    </div>

    <!-- Settings tab -->
    <div id="tab-settings" class="tab-content">
        <div id="logged-out">
            <p class="settings-text">Not connected to Passify.</p>
            <button id="login-btn" class="btn-primary">Login via Passify</button>
        </div>
        <div id="logged-in" style="display:none">
            <p class="settings-text">Connected to Passify.</p>
            <button id="logout-btn" class="btn-secondary">Logout</button>
        </div>
    </div>

    <script type="module" src="popup.js"></script>
</body>
</html>
```

**Step 6: Create popup.js**

Create `chrome-extension/popup/popup.js`:
```js
import { generate, strength } from '../shared/password-generator.js';

// ── Tabs ──────────────────────────────────────────────────────────────────
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab, .tab-content').forEach(el => el.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
    });
});

// ── Generator ─────────────────────────────────────────────────────────────
const slider = document.getElementById('length-slider');
const lengthDisplay = document.getElementById('length-display');
const passwordEl = document.getElementById('generated-password');
const strengthText = document.getElementById('strength-text');
const strengthFill = document.getElementById('strength-fill');

function getOpts() {
    return {
        length: parseInt(slider.value),
        lowercase: document.getElementById('opt-lowercase').checked,
        uppercase: document.getElementById('opt-uppercase').checked,
        numbers: document.getElementById('opt-numbers').checked,
        symbols: document.getElementById('opt-symbols').checked,
    };
}

function updateGenerator() {
    lengthDisplay.textContent = `${slider.value} characters`;
    const pwd = generate(getOpts());
    passwordEl.textContent = pwd;
    const s = strength(pwd);
    strengthText.textContent = s.label;
    strengthText.style.color = s.color;
    strengthFill.style.width = s.percent + '%';
    strengthFill.style.background = s.color;
}

slider.addEventListener('input', updateGenerator);
['opt-lowercase','opt-uppercase','opt-numbers','opt-symbols'].forEach(id => {
    document.getElementById(id).addEventListener('change', updateGenerator);
});
document.getElementById('generate-btn').addEventListener('click', updateGenerator);
document.getElementById('copy-btn').addEventListener('click', () => {
    navigator.clipboard.writeText(passwordEl.textContent);
    document.getElementById('copy-btn').textContent = '✓';
    setTimeout(() => document.getElementById('copy-btn').textContent = '⧉', 1500);
});

updateGenerator();

// ── Auth state ────────────────────────────────────────────────────────────
chrome.storage.local.get(['token', 'passifyUrl'], ({ token }) => {
    document.getElementById('logged-out').style.display = token ? 'none' : 'block';
    document.getElementById('logged-in').style.display = token ? 'block' : 'none';
});

document.getElementById('login-btn').addEventListener('click', () => {
    chrome.storage.local.get(['passifyUrl'], ({ passifyUrl }) => {
        const base = passifyUrl || 'http://localhost:8000';
        chrome.tabs.create({ url: `${base}/extension/auth` });
    });
});

document.getElementById('logout-btn').addEventListener('click', () => {
    chrome.storage.local.remove(['token'], () => {
        document.getElementById('logged-out').style.display = 'block';
        document.getElementById('logged-in').style.display = 'none';
    });
});

// ── Search ────────────────────────────────────────────────────────────────
let searchTimeout;
document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const q = e.target.value.trim();
    if (!q) { document.getElementById('search-results').innerHTML = ''; return; }
    searchTimeout = setTimeout(() => {
        chrome.runtime.sendMessage(
            { type: 'API_REQUEST', path: `/credentials/search?q=${encodeURIComponent(q)}`, method: 'GET' },
            (response) => {
                const results = document.getElementById('search-results');
                if (!response?.ok || !response.data?.data?.length) {
                    results.innerHTML = '<p class="no-results">No results found.</p>';
                    return;
                }
                results.innerHTML = response.data.data.map(c => `
                    <div class="result-item">
                        <div class="result-name">${c.name}</div>
                        <div class="result-org">${c.organization_name}</div>
                        <div class="result-actions">
                            <button class="copy-field" data-value="${c.email}">Copy email</button>
                            <button class="copy-field" data-value="${c.password}">Copy password</button>
                        </div>
                    </div>
                `).join('');
                results.querySelectorAll('.copy-field').forEach(btn => {
                    btn.addEventListener('click', () => {
                        navigator.clipboard.writeText(btn.dataset.value);
                        btn.textContent = 'Copied!';
                        setTimeout(() => btn.textContent = btn.textContent.includes('email') ? 'Copy email' : 'Copy password', 1500);
                    });
                });
            }
        );
    }, 400);
});
```

**Step 7: Create popup.css**

Create `chrome-extension/popup/popup.css`:
```css
* { box-sizing: border-box; margin: 0; padding: 0; }
body { width: 320px; font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #1f2937; background: #fff; }
.header { display: flex; align-items: center; gap: 8px; padding: 12px 14px; border-bottom: 1px solid #f3f4f6; }
.logo { width: 20px; height: 20px; }
.title { font-weight: 700; font-size: 15px; flex: 1; }
.tabs { display: flex; border-bottom: 1px solid #f3f4f6; }
.tab { flex: 1; padding: 8px; background: none; border: none; cursor: pointer; font-size: 12px; color: #6b7280; font-weight: 500; }
.tab.active { color: #4f46e5; border-bottom: 2px solid #4f46e5; }
.tab-content { display: none; padding: 14px; }
.tab-content.active { display: block; }
.strength-bar-wrapper { margin-bottom: 12px; }
.strength-label { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px; color: #6b7280; }
.strength-text { font-weight: 600; }
.strength-track { height: 6px; background: #e5e7eb; border-radius: 9999px; overflow: hidden; }
.strength-fill { height: 100%; border-radius: 9999px; transition: width .3s, background .3s; }
.length-wrapper { margin-bottom: 12px; }
.length-label { display: flex; justify-content: space-between; font-size: 11px; color: #6b7280; margin-bottom: 4px; }
input[type=range] { width: 100%; accent-color: #4f46e5; }
.options { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
.options label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px; }
.options input[type=checkbox] { accent-color: #4f46e5; }
.password-display { display: flex; align-items: center; gap: 6px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 10px; margin-bottom: 10px; }
.password-text { flex: 1; font-family: monospace; font-size: 13px; word-break: break-all; color: #111827; }
.icon-btn { background: none; border: none; cursor: pointer; font-size: 16px; color: #6b7280; }
.icon-btn:hover { color: #4f46e5; }
.btn-primary { width: 100%; background: #4f46e5; color: white; border: none; border-radius: 8px; padding: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-primary:hover { background: #4338ca; }
.btn-secondary { width: 100%; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; padding: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.search-input { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 7px 10px; font-size: 13px; outline: none; }
.search-input:focus { border-color: #4f46e5; }
.search-results { margin-top: 10px; }
.result-item { border: 1px solid #f3f4f6; border-radius: 8px; padding: 10px; margin-bottom: 6px; }
.result-name { font-weight: 600; font-size: 13px; }
.result-org { font-size: 11px; color: #6b7280; margin-bottom: 6px; }
.result-actions { display: flex; gap: 6px; }
.copy-field { flex: 1; background: #f3f4f6; border: none; border-radius: 6px; padding: 4px 8px; font-size: 11px; cursor: pointer; }
.copy-field:hover { background: #e5e7eb; }
.no-results { color: #6b7280; font-size: 12px; text-align: center; padding: 10px 0; }
.settings-text { color: #6b7280; font-size: 12px; margin-bottom: 10px; }
```

**Step 8: Load extension in Chrome and test manually**

```
1. Open Chrome → chrome://extensions/
2. Enable "Developer mode"
3. Click "Load unpacked" → select the chrome-extension/ folder
4. Click the Passify extension icon
5. Verify: Generator tab shows password + strength
6. Settings tab → click "Login via Passify" → completes OAuth flow → extension shows "Connected"
7. Navigate to any login page → verify autofill/save banners appear
```

**Step 9: Commit**

```bash
git add chrome-extension/
git commit -m "feat: scaffold Chrome extension with Manifest V3, popup, content script, and background worker"
```

---

## Task 9: PWA Scaffold (Vue 3 + Vite)

**Files:**
- Create: `pwa/package.json`
- Create: `pwa/vite.config.js`
- Create: `pwa/index.html`
- Create: `pwa/manifest.json`
- Create: `pwa/sw.js`
- Create: `pwa/src/main.js`
- Create: `pwa/src/api.js`
- Create: `pwa/src/App.vue`
- Create: `pwa/src/views/OrgsView.vue`
- Create: `pwa/src/views/GeneratorView.vue`
- Create: `pwa/src/views/SearchView.vue`
- Create: `pwa/src/views/ProfileView.vue`
- Create: `pwa/src/views/LoginView.vue`

**Step 1: Initialize PWA project**

```bash
cd F:/Projects/laravel/passify/pwa
npm create vite@latest . -- --template vue
npm install
```

Replace `pwa/package.json` to add:
```json
{
  "name": "passify-pwa",
  "private": true,
  "version": "1.0.0",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.4.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^7.0.0"
  }
}
```

**Step 2: Create vite.config.js**

Create `pwa/vite.config.js`:
```js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    base: '/pwa/',
});
```

**Step 3: Create manifest.json**

Create `pwa/manifest.json`:
```json
{
    "name": "Passify",
    "short_name": "Passify",
    "description": "Your credential vault",
    "start_url": "/pwa/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#4f46e5",
    "icons": [
        { "src": "/pwa/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
        { "src": "/pwa/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
    ]
}
```

**Step 4: Create index.html**

Create `pwa/index.html`:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Passify</title>
    <link rel="manifest" href="/pwa/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; }
    </style>
</head>
<body>
    <div id="app"></div>
    <script type="module" src="/pwa/src/main.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/pwa/sw.js');
        }
    </script>
</body>
</html>
```

**Step 5: Create service worker**

Create `pwa/sw.js`:
```js
const CACHE = 'passify-v1';
const APP_SHELL = ['/pwa/', '/pwa/index.html', '/pwa/src/main.js'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(APP_SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then(keys =>
        Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    ));
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    // Never cache API calls
    if (e.request.url.includes('/api/')) return;
    // Cache-first for app shell
    e.respondWith(
        caches.match(e.request).then(cached => cached || fetch(e.request))
    );
});
```

**Step 6: Create API client**

Create `pwa/src/api.js`:
```js
const BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000';

function getToken() {
    return localStorage.getItem('passify_token');
}

async function request(method, path, body = null) {
    const res = await fetch(`${BASE}/api${path}`, {
        method,
        headers: {
            'Authorization': `Bearer ${getToken()}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (res.status === 401) {
        localStorage.removeItem('passify_token');
        window.location.hash = '#/login';
        return null;
    }

    return res.json();
}

export const api = {
    login: (email, password) =>
        request('POST', '/auth/token', { email, password, device_name: 'pwa' }),
    logout: () => request('DELETE', '/auth/token'),
    getOrgs: () => request('GET', '/organizations'),
    getOrg: (id) => request('GET', `/organizations/${id}`),
    getCredentials: (orgId) => request('GET', `/organizations/${orgId}/credentials`),
    createCredential: (orgId, data) => request('POST', `/organizations/${orgId}/credentials`, data),
    updateCredential: (orgId, credId, data) => request('PUT', `/organizations/${orgId}/credentials/${credId}`, data),
    deleteCredential: (orgId, credId) => request('DELETE', `/organizations/${orgId}/credentials/${credId}`),
    search: (q, url) => request('GET', `/credentials/search?q=${encodeURIComponent(q)}&url=${encodeURIComponent(url || '')}`),
    isLoggedIn: () => !!getToken(),
    saveToken: (token) => localStorage.setItem('passify_token', token),
    clearToken: () => localStorage.removeItem('passify_token'),
};
```

**Step 7: Create main.js**

Create `pwa/src/main.js`:
```js
import { createApp } from 'vue';
import App from './App.vue';

createApp(App).mount('#app');
```

**Step 8: Create App.vue (router + bottom nav)**

Create `pwa/src/App.vue`:
```vue
<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from './api.js';
import LoginView from './views/LoginView.vue';
import OrgsView from './views/OrgsView.vue';
import GeneratorView from './views/GeneratorView.vue';
import SearchView from './views/SearchView.vue';
import ProfileView from './views/ProfileView.vue';

const route = ref(window.location.hash.replace('#/', '') || 'orgs');
const loggedIn = ref(api.isLoggedIn());

window.addEventListener('hashchange', () => {
    route.value = window.location.hash.replace('#/', '') || 'orgs';
});

function navigate(to) {
    window.location.hash = '#/' + to;
}

function onLogin() {
    loggedIn.value = true;
    navigate('orgs');
}

function onLogout() {
    loggedIn.value = false;
    navigate('login');
}
</script>

<template>
    <div class="app">
        <template v-if="!loggedIn || route === 'login'">
            <LoginView @login="onLogin" />
        </template>
        <template v-else>
            <main class="main-content">
                <OrgsView v-if="route === 'orgs' || route === ''" />
                <GeneratorView v-else-if="route === 'generator'" />
                <SearchView v-else-if="route === 'search'" />
                <ProfileView v-else-if="route === 'profile'" @logout="onLogout" />
            </main>
            <nav class="bottom-nav">
                <button @click="navigate('orgs')" :class="{ active: route === 'orgs' || route === '' }">
                    <span class="nav-icon">🏢</span><span class="nav-label">Orgs</span>
                </button>
                <button @click="navigate('generator')" :class="{ active: route === 'generator' }">
                    <span class="nav-icon">🔑</span><span class="nav-label">Generator</span>
                </button>
                <button @click="navigate('search')" :class="{ active: route === 'search' }">
                    <span class="nav-icon">🔍</span><span class="nav-label">Search</span>
                </button>
                <button @click="navigate('profile')" :class="{ active: route === 'profile' }">
                    <span class="nav-icon">👤</span><span class="nav-label">Profile</span>
                </button>
            </nav>
        </template>
    </div>
</template>

<style>
.app { display: flex; flex-direction: column; min-height: 100vh; max-width: 480px; margin: 0 auto; }
.main-content { flex: 1; overflow-y: auto; padding-bottom: 64px; }
.bottom-nav { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 480px; display: flex; background: white; border-top: 1px solid #e5e7eb; height: 60px; }
.bottom-nav button { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; border: none; background: none; cursor: pointer; color: #6b7280; font-size: 11px; }
.bottom-nav button.active { color: #4f46e5; }
.nav-icon { font-size: 20px; line-height: 1; }
.nav-label { font-size: 10px; font-weight: 500; }
</style>
```

**Step 9: Create LoginView.vue**

Create `pwa/src/views/LoginView.vue`:
```vue
<script setup>
import { ref } from 'vue';
import { api } from '../api.js';

const emit = defineEmits(['login']);
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function login() {
    loading.value = true;
    error.value = '';
    const res = await api.login(email.value, password.value);
    loading.value = false;
    if (res?.token) {
        api.saveToken(res.token);
        emit('login');
    } else {
        error.value = 'Invalid credentials.';
    }
}
</script>

<template>
    <div class="login-page">
        <div class="login-card">
            <h1 class="login-title">Passify</h1>
            <p class="login-sub">Sign in to your vault</p>
            <div v-if="error" class="error">{{ error }}</div>
            <form @submit.prevent="login">
                <div class="field">
                    <label>Email</label>
                    <input v-model="email" type="email" placeholder="you@example.com" required autocomplete="email">
                </div>
                <div class="field">
                    <label>Password</label>
                    <input v-model="password" type="password" placeholder="Password" required autocomplete="current-password">
                </div>
                <button type="submit" :disabled="loading" class="btn-login">
                    {{ loading ? 'Signing in…' : 'Sign In' }}
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f9fafb; padding: 24px; }
.login-card { background: white; border-radius: 16px; padding: 32px 24px; width: 100%; max-width: 360px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.login-title { font-size: 28px; font-weight: 800; color: #4f46e5; text-align: center; margin-bottom: 4px; }
.login-sub { text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 24px; }
.error { background: #fef2f2; color: #dc2626; border-radius: 8px; padding: 10px 12px; font-size: 13px; margin-bottom: 16px; }
.field { margin-bottom: 14px; }
.field label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
.field input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; outline: none; }
.field input:focus { border-color: #4f46e5; }
.btn-login { width: 100%; background: #4f46e5; color: white; border: none; border-radius: 10px; padding: 12px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; }
.btn-login:disabled { opacity: .6; }
</style>
```

**Step 10: Create GeneratorView.vue**

Create `pwa/src/views/GeneratorView.vue`:
```vue
<script setup>
import { ref, computed, onMounted } from 'vue';
import { generate, strength } from '../../shared/password-generator.js';

// Copy password-generator.js from chrome-extension/shared/ to pwa/shared/ during build.
// For now, reference it via relative path (adjust after setting up build script).

const length = ref(16);
const opts = ref({ lowercase: true, uppercase: true, numbers: true, symbols: true });
const password = ref('');
const copied = ref(false);

const s = computed(() => strength(password.value));

function generatePassword() {
    password.value = generate({ length: length.value, ...opts.value });
}

function copy() {
    navigator.clipboard.writeText(password.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 1500);
}

onMounted(generatePassword);
</script>

<template>
    <div class="view">
        <h2 class="view-title">Password Generator</h2>

        <div class="strength-block">
            <div class="strength-row">
                <span class="label-sm">Strength</span>
                <span class="label-sm font-semibold" :style="{ color: s.color }">{{ s.label }}</span>
            </div>
            <div class="strength-track">
                <div class="strength-fill" :style="{ width: s.percent + '%', background: s.color }"></div>
            </div>
        </div>

        <div class="field-block">
            <div class="length-row">
                <span class="label-sm">Password length</span>
                <span class="label-sm font-semibold">{{ length }} characters</span>
            </div>
            <input type="range" v-model.number="length" min="8" max="64" @input="generatePassword" class="slider">
        </div>

        <div class="options-block">
            <label v-for="(key, label) in { 'Lowercase (abc)': 'lowercase', 'Uppercase (ABC)': 'uppercase', 'Numbers (123)': 'numbers', 'Randomized symbols (!#$)': 'symbols' }" :key="key" class="option-label">
                <input type="checkbox" v-model="opts[label]" @change="generatePassword"> {{ key }}
            </label>
        </div>

        <div class="password-block">
            <span class="password-text">{{ password }}</span>
            <button @click="copy" class="copy-btn">{{ copied ? '✓' : '⧉' }}</button>
        </div>

        <button @click="generatePassword" class="btn-primary">Generate</button>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.view-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 20px; }
.strength-block, .field-block { margin-bottom: 16px; }
.strength-row, .length-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
.label-sm { font-size: 13px; color: #6b7280; }
.font-semibold { font-weight: 600; }
.strength-track { height: 8px; background: #e5e7eb; border-radius: 9999px; overflow: hidden; }
.strength-fill { height: 100%; border-radius: 9999px; transition: width .3s, background .3s; }
.slider { width: 100%; accent-color: #4f46e5; }
.options-block { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.option-label { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; }
input[type=checkbox] { accent-color: #4f46e5; width: 16px; height: 16px; }
.password-block { display: flex; align-items: center; gap: 10px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; }
.password-text { flex: 1; font-family: monospace; font-size: 15px; word-break: break-all; color: #111827; }
.copy-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280; }
.btn-primary { width: 100%; background: #4f46e5; color: white; border: none; border-radius: 12px; padding: 14px; font-size: 15px; font-weight: 600; cursor: pointer; }
</style>
```

**Step 11: Create OrgsView.vue, SearchView.vue, ProfileView.vue**

These follow the same Vue pattern. Create `pwa/src/views/OrgsView.vue`:
```vue
<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api.js';

const orgs = ref([]);
const selected = ref(null);
const credentials = ref([]);
const loading = ref(true);
const revealedId = ref(null);

onMounted(async () => {
    const res = await api.getOrgs();
    orgs.value = res?.data || [];
    loading.value = false;
});

async function selectOrg(org) {
    selected.value = org;
    const res = await api.getCredentials(org.id);
    credentials.value = res?.data || [];
}

function back() {
    selected.value = null;
    credentials.value = [];
    revealedId.value = null;
}

function toggleReveal(id) {
    revealedId.value = revealedId.value === id ? null : id;
}

function copy(text) {
    navigator.clipboard.writeText(text);
}
</script>

<template>
    <div class="view">
        <template v-if="!selected">
            <h2 class="view-title">Organizations</h2>
            <div v-if="loading" class="loading">Loading…</div>
            <div v-else-if="!orgs.length" class="empty">No organizations yet.</div>
            <div v-else class="list">
                <div v-for="org in orgs" :key="org.id" class="card" @click="selectOrg(org)">
                    <div class="card-name">{{ org.name }}</div>
                    <div class="card-meta">{{ org.credentials_count }} credentials · {{ org.role }}</div>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="header-row">
                <button @click="back" class="back-btn">← Back</button>
                <h2 class="view-title">{{ selected.name }}</h2>
            </div>
            <div v-if="!credentials.length" class="empty">No credentials in this org.</div>
            <div v-else class="list">
                <div v-for="cred in credentials" :key="cred.id" class="card">
                    <div class="cred-top">
                        <div>
                            <div class="card-name">{{ cred.name }}</div>
                            <div class="card-meta">{{ cred.service_type }} · {{ cred.email }}</div>
                        </div>
                        <div class="cred-actions">
                            <button @click="toggleReveal(cred.id)" class="action-btn">
                                {{ revealedId === cred.id ? 'Hide' : 'Show' }}
                            </button>
                            <button @click="copy(cred.password)" class="action-btn">Copy</button>
                        </div>
                    </div>
                    <div v-if="revealedId === cred.id" class="password-reveal">{{ cred.password }}</div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.view-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; }
.header-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.header-row .view-title { margin-bottom: 0; }
.back-btn { background: none; border: none; color: #4f46e5; font-size: 14px; cursor: pointer; padding: 0; font-weight: 500; }
.loading, .empty { color: #6b7280; font-size: 14px; text-align: center; padding: 32px 0; }
.list { display: flex; flex-direction: column; gap: 10px; }
.card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; cursor: pointer; }
.card-name { font-weight: 600; font-size: 15px; color: #111827; }
.card-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
.cred-top { display: flex; align-items: flex-start; justify-content: space-between; }
.cred-actions { display: flex; gap: 6px; }
.action-btn { background: #f3f4f6; border: none; border-radius: 8px; padding: 5px 10px; font-size: 12px; cursor: pointer; color: #374151; }
.action-btn:hover { background: #e5e7eb; }
.password-reveal { margin-top: 10px; font-family: monospace; font-size: 13px; background: #f9fafb; border-radius: 8px; padding: 8px 10px; word-break: break-all; color: #111827; }
</style>
```

Create `pwa/src/views/SearchView.vue`:
```vue
<script setup>
import { ref } from 'vue';
import { api } from '../api.js';

const query = ref('');
const results = ref([]);
let timer;

function onInput() {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (!query.value.trim()) { results.value = []; return; }
        const res = await api.search(query.value);
        results.value = res?.data || [];
    }, 400);
}

function copy(text) {
    navigator.clipboard.writeText(text);
}
</script>

<template>
    <div class="view">
        <h2 class="view-title">Search</h2>
        <input v-model="query" @input="onInput" type="text" placeholder="Search credentials…" class="search-input">
        <div v-if="!results.length && query" class="empty">No results.</div>
        <div class="list">
            <div v-for="c in results" :key="c.id" class="card">
                <div class="card-name">{{ c.name }}</div>
                <div class="card-meta">{{ c.organization_name }} · {{ c.email }}</div>
                <div class="card-actions">
                    <button @click="copy(c.email)" class="action-btn">Copy email</button>
                    <button @click="copy(c.password)" class="action-btn">Copy password</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.view-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; }
.search-input { width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 12px; font-size: 15px; outline: none; margin-bottom: 16px; }
.search-input:focus { border-color: #4f46e5; }
.empty { color: #6b7280; font-size: 14px; text-align: center; padding: 20px 0; }
.list { display: flex; flex-direction: column; gap: 10px; }
.card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; }
.card-name { font-weight: 600; font-size: 15px; color: #111827; }
.card-meta { font-size: 12px; color: #6b7280; margin-top: 2px; margin-bottom: 10px; }
.card-actions { display: flex; gap: 8px; }
.action-btn { background: #f3f4f6; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; cursor: pointer; }
.action-btn:hover { background: #e5e7eb; }
</style>
```

Create `pwa/src/views/ProfileView.vue`:
```vue
<script setup>
import { api } from '../api.js';

const emit = defineEmits(['logout']);

async function logout() {
    await api.logout();
    api.clearToken();
    emit('logout');
}
</script>

<template>
    <div class="view">
        <h2 class="view-title">Profile</h2>
        <div class="card">
            <p class="info">Logged in to Passify.</p>
            <button @click="logout" class="btn-logout">Sign Out</button>
        </div>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.view-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; }
.card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; }
.info { color: #6b7280; font-size: 14px; margin-bottom: 16px; }
.btn-logout { background: #fee2e2; color: #dc2626; border: none; border-radius: 10px; padding: 12px 20px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
</style>
```

**Step 12: Copy shared generator into pwa**

```bash
mkdir -p F:/Projects/laravel/passify/pwa/shared
cp F:/Projects/laravel/passify/chrome-extension/shared/password-generator.js F:/Projects/laravel/passify/pwa/shared/password-generator.js
```

Update the import in `GeneratorView.vue` to: `import { generate, strength } from '../../shared/password-generator.js';`

**Step 13: Add .env for PWA API URL**

Create `pwa/.env`:
```
VITE_API_URL=http://localhost:8000
```

**Step 14: Build and test PWA**

```bash
cd F:/Projects/laravel/passify/pwa
npm run dev
```

Open `http://localhost:5173/pwa/` in browser. Test:
- Login form → enters credentials → lands on Orgs screen
- Orgs screen shows organizations
- Generator screen: password generates, strength updates, copy works
- Search screen: typing returns results
- Profile → Sign Out → returns to login

For mobile: open Chrome DevTools → toggle device toolbar → verify bottom nav is accessible.

**Step 15: Commit**

```bash
git add pwa/ chrome-extension/shared/
git commit -m "feat: scaffold Vue 3 PWA with all 4 screens and service worker"
```

---

## Task 10: Final integration and .env documentation

**Files:**
- Modify: `.env.example`
- Modify: `README.md` (only if it exists and covers setup)

**Step 1: Document new environment variables**

Add to `.env.example`:
```
# Chrome Extension & PWA
SANCTUM_STATEFUL_DOMAINS=localhost
SESSION_DOMAIN=localhost
```

**Step 2: Run full test suite**

```bash
cd F:/Projects/laravel/passify
php artisan test
```
Expected: all tests PASS including the new API tests.

**Step 3: Commit**

```bash
git add .env.example
git commit -m "docs: document Sanctum environment variables for extension and PWA"
```

---

## Summary of New Files

| File | Purpose |
|------|---------|
| `routes/api.php` | REST API routes |
| `app/Http/Controllers/Api/AuthController.php` | Token login/logout |
| `app/Http/Controllers/Api/OrganizationController.php` | Org list + detail |
| `app/Http/Controllers/Api/CredentialController.php` | Credential CRUD + search |
| `app/Http/Controllers/ExtensionAuthController.php` | OAuth-style token handoff |
| `resources/views/extension-auth.blade.php` | Extension connect page |
| `resources/js/password-generator.js` | Shared generator module |
| `resources/views/components/password-generator-modal.blade.php` | Web app modal |
| `chrome-extension/` | Full Manifest V3 extension |
| `pwa/` | Full Vue 3 PWA |
| `tests/Feature/Api/` | API feature tests |
