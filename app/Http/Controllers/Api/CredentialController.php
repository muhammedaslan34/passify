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

        return response()->json([
            'data' => $organization->credentials()->get()->map(fn ($c) => $this->format($c)),
        ]);
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
        $url  = $request->query('url', '');
        $q    = $request->query('q', '');

        // SECURITY: only search within orgs the user belongs to
        $orgIds = $user->organizations()->pluck('organizations.id');

        $credentials = Credential::whereIn('organization_id', $orgIds)
            ->when($url, fn ($query) => $query->where('website_url', 'like', "%{$url}%"))
            ->when($q,   fn ($query) => $query->where('name', 'like', "%{$q}%"))
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
            'id'              => $c->id,
            'organization_id' => $c->organization_id,
            'service_type'    => $c->service_type,
            'name'            => $c->name,
            'website_url'     => $c->website_url,
            'email'           => $c->email,
            'password'        => $c->password,
            'note'            => $c->note,
        ];
    }
}
