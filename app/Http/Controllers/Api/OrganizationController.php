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
                'id'                => $org->id,
                'name'              => $org->name,
                'website_url'       => $org->website_url,
                'description'       => $org->description,
                'role'              => $org->pivot->role,
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
            ->first()
            ->pivot
            ->role;

        return response()->json(['data' => [
            'id'                => $organization->id,
            'name'              => $organization->name,
            'website_url'       => $organization->website_url,
            'description'       => $organization->description,
            'role'              => $role,
            'credentials_count' => $organization->credentials()->count(),
        ]]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $org = Organization::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $org->members()->attach($request->user()->id, ['role' => 'owner']);

        return response()->json(['data' => [
            'id'                => $org->id,
            'name'              => $org->name,
            'website_url'       => $org->website_url,
            'description'       => $org->description,
            'role'              => 'owner',
            'credentials_count' => 0,
        ]], 201);
    }
}
