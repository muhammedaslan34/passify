<?php

use App\Models\Credential;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public int $orgCount = 0;
    public int $credentialCount = 0;
    public int $ownedCount = 0;
    public $recentOrgs;

    public function mount(): void
    {
        $user = auth()->user();

        $this->orgCount = $user->organizations()->count();
        $this->ownedCount = $user->organizations()->wherePivot('role', 'owner')->count();

        $orgIds = $user->organizations()->pluck('organizations.id');
        $this->credentialCount = Credential::whereIn('organization_id', $orgIds)->count();

        $this->recentOrgs = $user->organizations()
            ->withPivot('role')
            ->withCount('credentials')
            ->latest('organization_user.created_at')
            ->take(6)
            ->get();
    }
}; ?>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Organizations</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $orgCount }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Credentials Stored</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $credentialCount }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Orgs You Own</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $ownedCount }}</p>
                </div>
            </div>
        </div>

        {{-- Recent Organizations --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">My Organizations</h3>
                <a href="{{ route('organizations.index') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition">View all →</a>
            </div>

            @if($recentOrgs->isEmpty())
                <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                    <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 mb-4">You don't belong to any organizations yet.</p>
                    <a href="{{ route('organizations.create') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
                        Create your first organization
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($recentOrgs as $org)
                        <a href="{{ route('organizations.show', $org) }}" wire:navigate
                           class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-indigo-200 hover:shadow-md transition-all">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($org->name, 0, 2)) }}
                                </div>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $org->pivot->role === 'owner' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($org->pivot->role) }}
                                </span>
                            </div>
                            <h4 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition truncate">{{ $org->name }}</h4>
                            @if($org->website_url)
                                <p class="text-xs text-gray-400 truncate mt-0.5">{{ $org->website_url }}</p>
                            @endif
                            <p class="text-sm text-gray-500 mt-3">{{ $org->credentials_count }} credential{{ $org->credentials_count !== 1 ? 's' : '' }}</p>
                        </a>
                    @endforeach

                    <a href="{{ route('organizations.create') }}" wire:navigate
                       class="flex flex-col items-center justify-center bg-white rounded-2xl border-2 border-dashed border-gray-200 p-5 hover:border-indigo-300 hover:bg-indigo-50/30 transition-all text-gray-400 hover:text-indigo-500 gap-2 min-h-[120px]">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span class="text-sm font-medium">New Organization</span>
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
