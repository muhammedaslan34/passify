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
