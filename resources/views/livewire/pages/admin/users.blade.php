<?php

use App\Models\User;
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
    public function users()
    {
        return User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%'))
            ->withCount('organizations')
            ->orderBy('name')
            ->paginate(20);
    }

    public function toggleSuperAdmin(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'You cannot change your own super admin status.');
            return;
        }

        $user = User::findOrFail($userId);
        $user->update(['is_super_admin' => !$user->is_super_admin]);

        $status = $user->is_super_admin ? 'granted' : 'revoked';
        session()->flash('status', "Super admin access {$status} for {$user->name}.");
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">All Users</h2>
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
        @if(session('error'))
            <div class="bg-red-50 text-red-700 border border-red-200 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Search --}}
        <div class="relative max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search users…"
                   class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/70">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">User</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Orgs</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Super Admin</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->users as $user)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full {{ $user->is_super_admin ? 'bg-red-100' : 'bg-gray-100' }} flex items-center justify-center font-semibold text-sm {{ $user->is_super_admin ? 'text-red-700' : 'text-gray-600' }} shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">
                                            {{ $user->name }}
                                            @if($user->id === auth()->id()) <span class="text-gray-400 font-normal text-xs">(you)</span> @endif
                                        </p>
                                        <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 text-center hidden sm:table-cell">{{ $user->organizations_count }}</td>
                            <td class="px-4 py-4 text-center">
                                <button wire:click="toggleSuperAdmin({{ $user->id }})"
                                        @if($user->id === auth()->id()) disabled title="Cannot change your own status" @endif
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full transition
                                            {{ $user->is_super_admin
                                                ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                                : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}
                                            {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                    @if($user->is_super_admin)
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1.5a.75.75 0 01.75.75V7.5h-1.5V2.25A.75.75 0 0112 1.5zM11.25 7.5v5.69l-3.22-3.22a.75.75 0 00-1.06 1.06l4.5 4.5a.75.75 0 001.06 0l4.5-4.5a.75.75 0 10-1.06-1.06l-3.22 3.22V7.5h-1.5z"/></svg>
                                        Super Admin
                                    @else
                                        User
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-400 hidden md:table-cell">{{ $user->created_at->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400">
                                No users found{{ $search ? ' matching "' . $search . '"' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($this->users->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $this->users->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
