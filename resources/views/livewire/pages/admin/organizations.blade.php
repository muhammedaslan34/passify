<?php

use App\Models\Organization;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function organizations()
    {
        return Organization::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('website_url', 'like', '%' . $this->search . '%'))
            ->withCount(['members', 'credentials'])
            ->with('creator:id,name')
            ->latest()
            ->paginate(20);
    }

    public function deleteOrganization(Organization $org): void
    {
        $org->delete();
        session()->flash('status', "Organization \"{$org->name}\" deleted.");
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">All Organizations</h2>
        </div>
        <span class="text-sm text-gray-500">Admin</span>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if(session('status'))
            <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        {{-- Search --}}
        <div class="relative max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search organizations…"
                   class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/70">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Organization</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Created By</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Members</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Credentials</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->organizations as $org)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($org->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $org->name }}</p>
                                        @if($org->website_url)
                                            <p class="text-xs text-gray-400 truncate">{{ $org->website_url }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 hidden sm:table-cell">{{ $org->creator?->name ?? '—' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700 text-center">{{ $org->members_count }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700 text-center">{{ $org->credentials_count }}</td>
                            <td class="px-4 py-4 text-sm text-gray-400 hidden md:table-cell">{{ $org->created_at->format('M j, Y') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('organizations.show', $org) }}" wire:navigate
                                       class="text-xs font-semibold px-2.5 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition">
                                        Enter
                                    </a>
                                    <button wire:click="deleteOrganization({{ $org->id }})"
                                            wire:confirm="Delete {{ $org->name }}? This is permanent."
                                            class="text-xs font-semibold px-2.5 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">
                                No organizations found{{ $search ? ' matching "' . $search . '"' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($this->organizations->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $this->organizations->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
