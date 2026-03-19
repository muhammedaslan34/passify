<?php

namespace App\Models;

use App\Exceptions\OldSlugRedirectException;
use App\Models\OrganizationSlugHistory;
use App\Services\SlugService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'website_url', 'description', 'created_by'];

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
        ]);

        $this->update(['slug' => $newSlug]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────────

    public function slugHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrganizationSlugHistory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function isMemberOf(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isOwner(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
