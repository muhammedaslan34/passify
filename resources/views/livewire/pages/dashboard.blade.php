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

        {{-- Welcome Banner --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl p-6 sm:p-8">
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-indigo-200 text-sm font-medium mb-1">Welcome back,</p>
                    <h2 class="text-white text-2xl sm:text-3xl font-bold tracking-tight">{{ auth()->user()->name }}</h2>
                    <p class="text-indigo-200 text-sm mt-2">
                        {{ $orgCount }} {{ Str::plural('organization', $orgCount) }} &middot; {{ $credentialCount }} stored {{ Str::plural('credential', $credentialCount) }}
                    </p>
                </div>
                <div class="hidden sm:flex w-14 h-14 rounded-2xl bg-white/10 items-center justify-center shrink-0">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-12 right-16 w-56 h-56 rounded-full bg-white/5 pointer-events-none"></div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 rounded-l-2xl"></div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Organizations</p>
                    <p class="text-3xl font-bold text-gray-900 leading-none mt-1">{{ $orgCount }}</p>
                </div>
            </div>

            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500 rounded-l-2xl"></div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Credentials Stored</p>
                    <p class="text-3xl font-bold text-gray-900 leading-none mt-1">{{ $credentialCount }}</p>
                </div>
            </div>

            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500 rounded-l-2xl"></div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Orgs You Own</p>
                    <p class="text-3xl font-bold text-gray-900 leading-none mt-1">{{ $ownedCount }}</p>
                </div>
            </div>

        </div>

        {{-- Recent Organizations --}}
        <div>
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">My Organizations</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Recently joined workspaces</p>
                </div>
                <a href="{{ route('organizations.index') }}" wire:navigate
                   class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-200">
                    View all
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </div>

            @if($recentOrgs->isEmpty())
                <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-gray-800 mb-1">No organizations yet</h4>
                    <p class="text-sm text-gray-500 mb-5">Create your first workspace to start storing credentials.</p>
                    <a href="{{ route('organizations.create') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors duration-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Create your first organization
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($recentOrgs as $org)
                        <a href="{{ route('organizations.show', $org) }}" wire:navigate
                           class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-indigo-200 hover:shadow-md transition-all duration-200 cursor-pointer">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm">
                                    {{ strtoupper(substr($org->name, 0, 2)) }}
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                                    {{ $org->pivot->role === 'owner'
                                        ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                        : 'bg-slate-50 text-slate-600 ring-1 ring-slate-200' }}">
                                    {{ ucfirst($org->pivot->role) }}
                                </span>
                            </div>
                            <h4 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors duration-200 truncate">{{ $org->name }}</h4>
                            @if($org->website_url)
                                <p class="text-xs text-gray-400 truncate mt-0.5">{{ $org->website_url }}</p>
                            @endif
                            <div class="flex items-center gap-1.5 mt-3">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                </svg>
                                <span class="text-sm text-gray-500">{{ $org->credentials_count }} {{ Str::plural('credential', $org->credentials_count) }}</span>
                            </div>
                        </a>
                    @endforeach

                    <a href="{{ route('organizations.create') }}" wire:navigate
                       class="group flex flex-col items-center justify-center bg-white rounded-2xl border-2 border-dashed border-gray-200 p-5 hover:border-indigo-300 hover:bg-indigo-50/40 transition-all duration-200 text-gray-400 hover:text-indigo-500 gap-2 min-h-[148px] cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-gray-50 group-hover:bg-indigo-100 flex items-center justify-center transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold">New Organization</span>
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
