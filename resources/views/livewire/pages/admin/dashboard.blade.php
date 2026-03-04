<?php

use App\Models\Credential;
use App\Models\Organization;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public int $orgCount = 0;
    public int $userCount = 0;
    public int $credentialCount = 0;
    public int $superAdminCount = 0;
    public $recentOrgs;

    public function mount(): void
    {
        $this->orgCount       = Organization::count();
        $this->userCount      = User::count();
        $this->credentialCount = Credential::count();
        $this->superAdminCount = User::where('is_super_admin', true)->count();

        $this->recentOrgs = Organization::latest()
            ->take(5)
            ->withCount(['members', 'credentials'])
            ->with('creator:id,name')
            ->get();
    }
}; ?>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- Admin Banner --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 sm:p-8">
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-red-400 mb-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                        Super Admin
                    </span>
                    <h2 class="text-white text-2xl sm:text-3xl font-bold tracking-tight">System Overview</h2>
                    <p class="text-slate-400 text-sm mt-2">{{ $orgCount }} organizations &middot; {{ $userCount }} users &middot; {{ $credentialCount }} credentials</p>
                </div>
                <div class="hidden sm:flex w-14 h-14 rounded-2xl bg-white/5 items-center justify-center shrink-0">
                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-12 right-16 w-56 h-56 rounded-full bg-white/5 pointer-events-none"></div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">

            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 rounded-l-2xl"></div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Organizations</p>
                    <p class="text-2xl font-bold text-gray-900 leading-none mt-1">{{ $orgCount }}</p>
                </div>
            </div>

            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l-2xl"></div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900 leading-none mt-1">{{ $userCount }}</p>
                </div>
            </div>

            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500 rounded-l-2xl"></div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Credentials</p>
                    <p class="text-2xl font-bold text-gray-900 leading-none mt-1">{{ $credentialCount }}</p>
                </div>
            </div>

            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500 rounded-l-2xl"></div>
                <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Super Admins</p>
                    <p class="text-2xl font-bold text-gray-900 leading-none mt-1">{{ $superAdminCount }}</p>
                </div>
            </div>

        </div>

        {{-- Quick Nav --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('admin.organizations') }}" wire:navigate
               class="group flex items-center gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-indigo-200 hover:shadow-md transition-all duration-200 cursor-pointer">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 group-hover:bg-indigo-100 flex items-center justify-center shrink-0 transition-colors duration-200">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-800 group-hover:text-indigo-600 transition-colors duration-200">Manage Organizations</p>
                    <p class="text-sm text-gray-400 mt-0.5">View, enter, or delete any organization</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-400 transition-colors duration-200 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>

            <a href="{{ route('admin.users') }}" wire:navigate
               class="group flex items-center gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-blue-200 hover:shadow-md transition-all duration-200 cursor-pointer">
                <div class="w-11 h-11 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center shrink-0 transition-colors duration-200">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-800 group-hover:text-blue-600 transition-colors duration-200">Manage Users</p>
                    <p class="text-sm text-gray-400 mt-0.5">View all users and toggle super admin access</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-blue-400 transition-colors duration-200 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>

        {{-- Recent Organizations --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Recently Created Organizations</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Latest workspaces added to the system</p>
                </div>
                <a href="{{ route('admin.organizations') }}" wire:navigate
                   class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-200">
                    View all
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentOrgs as $org)
                    <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50/60 transition-colors duration-150">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-sm">
                            {{ strtoupper(substr($org->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-800 truncate">{{ $org->name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">by {{ $org->creator?->name ?? 'Unknown' }} &middot; {{ $org->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="hidden sm:flex items-center gap-3 shrink-0">
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-50 px-2.5 py-1 rounded-lg ring-1 ring-gray-100">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                </svg>
                                {{ $org->members_count }} {{ Str::plural('member', $org->members_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-50 px-2.5 py-1 rounded-lg ring-1 ring-gray-100">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                </svg>
                                {{ $org->credentials_count }} {{ Str::plural('cred', $org->credentials_count) }}
                            </span>
                        </div>
                        <a href="{{ route('organizations.show', $org) }}" wire:navigate
                           class="text-xs font-bold px-3.5 py-2 bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-100 transition-colors duration-200 shrink-0 cursor-pointer">
                            Enter
                        </a>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-400">No organizations have been created yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
