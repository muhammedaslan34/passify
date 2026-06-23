<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Organization;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CredentialController extends Controller
{
    public function index(Request $request, Organization $organization)
    {
        if (! $organization->isMemberOf($request->user())) {
            abort(403);
        }

        return response()->json([
            'data' => $organization->credentials()->with('serviceType')->get()->map(fn ($c) => $this->format($c)),
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        if (! $organization->isOwner($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'service_type'    => ['nullable', 'string', Rule::exists('service_types', 'slug')],
            'name'            => ['required', 'string', 'max:255'],
            'website_url'     => ['nullable', 'url', 'max:255'],
            'email'           => ['nullable', 'email', 'max:255'],
            'password'        => ['required', 'string', 'max:1000'],
            'note'            => ['nullable', 'string', 'max:2000'],
        ]);

        $credential = $organization->credentials()->create(
            Arr::except($validated, ['service_type', 'service_type_id']) + [
                'service_type_id' => $this->resolveServiceTypeId($validated),
            ]
        );

        return response()->json(['data' => $this->format($credential)], 201);
    }

    public function update(Request $request, Organization $organization, Credential $credential)
    {
        if (! $organization->isOwner($request->user())) {
            abort(403);
        }

        abort_if($credential->organization_id !== $organization->id, 404);

        $validated = $request->validate([
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'service_type'    => ['nullable', 'string', Rule::exists('service_types', 'slug')],
            'name'            => ['sometimes', 'required', 'string', 'max:255'],
            'website_url'     => ['sometimes', 'nullable', 'url', 'max:255'],
            'email'           => ['sometimes', 'nullable', 'email', 'max:255'],
            'password'        => ['sometimes', 'string', 'max:1000'],
            'note'            => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $credential->update(
            Arr::except($validated, ['service_type', 'service_type_id']) + [
                'service_type_id' => $this->resolveServiceTypeId($validated) ?? $credential->service_type_id,
            ]
        );

        return response()->json(['data' => $this->format($credential->fresh()->load('serviceType'))]);
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
        $url  = $request->query('url', '');
        $q    = $request->query('q', '');

        // SECURITY: only search within orgs the user belongs to
        $orgIds = $user->organizations()->pluck('organizations.id');

        $credentials = Credential::whereIn('organization_id', $orgIds)
            ->when($url, fn ($query) => $query->where('website_url', 'like', "%{$url}%"))
            ->when($q,   fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->with(['organization:id,name', 'serviceType'])
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
            'id'                 => $c->id,
            'organization_id'    => $c->organization_id,
            'service_type_id'    => $c->service_type_id,
            'service_type'       => $c->serviceType?->slug,
            'service_type_name'  => $c->serviceType?->name,
            'service_type_color' => $c->serviceType?->color,
            'name'               => $c->name,
            'website_url'        => $c->website_url,
            'email'              => $c->email,
            'password'           => $c->password,
            'note'               => $c->note,
        ];
    }

    /**
     * Resolve the incoming service type to an id. Prefers an explicit id,
     * falls back to the legacy slug (the Chrome extension sends service_type: 'other').
     */
    private function resolveServiceTypeId(array $validated): ?int
    {
        if (! empty($validated['service_type_id'])) {
            return (int) $validated['service_type_id'];
        }

        if (! empty($validated['service_type'])) {
            return (int) ServiceType::where('slug', $validated['service_type'])->value('id');
        }

        return (int) ServiceType::where('slug', 'other')->value('id')
            ?: (int) ServiceType::active()->ordered()->value('id')
            ?: null;
    }
}
