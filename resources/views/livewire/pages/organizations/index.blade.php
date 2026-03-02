<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public $organizations;

    public function mount(): void
    {
        $this->organizations = auth()->user()
            ->organizations()
            ->withPivot('role')
            ->withCount('credentials')
            ->orderBy('name')
            ->get();
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Organizations</h2>
        <a href="{{ route('organizations.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Organization
        </a>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($organizations->isEmpty())
            <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-16 text-center">
                <div class="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No organizations yet</h3>
                <p class="text-gray-500 mb-6 max-w-sm mx-auto">Create your first organization to start storing credentials for a project or website.</p>
                <a href="{{ route('organizations.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
                    Create Organization
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($organizations as $org)
                    <a href="{{ route('organizations.show', $org) }}" wire:navigate
                       class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:border-indigo-200 hover:shadow-md transition-all flex flex-col">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-bold text-base shrink-0">
                                {{ strtoupper(substr($org->name, 0, 2)) }}
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $org->pivot->role === 'owner' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($org->pivot->role) }}
                            </span>
                        </div>

                        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition text-lg truncate">
                            {{ $org->name }}
                        </h3>

                        @if($org->website_url)
                            <p class="text-sm text-gray-400 truncate mt-1">{{ $org->website_url }}</p>
                        @endif

                        @if($org->description)
                            <p class="text-sm text-gray-500 mt-2 line-clamp-2 flex-1">{{ $org->description }}</p>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                            <span class="text-sm text-gray-500 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                </svg>
                                {{ $org->credentials_count }} credential{{ $org->credentials_count !== 1 ? 's' : '' }}
                            </span>
                            <span class="text-xs text-indigo-500 font-medium group-hover:underline">View →</span>
                        </div>
                    </a>
                @endforeach

                <a href="{{ route('organizations.create') }}" wire:navigate
                   class="flex flex-col items-center justify-center bg-white rounded-2xl border-2 border-dashed border-gray-200 p-6 hover:border-indigo-300 hover:bg-indigo-50/30 transition-all text-gray-400 hover:text-indigo-500 gap-3 min-h-[180px]">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span class="text-sm font-semibold">New Organization</span>
                </a>
            </div>
        @endif

    </div>
</div>
