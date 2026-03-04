<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var bool */
    public bool $showUserModal = false;

    /** @var 'create'|'edit' */
    public string $userModalMode = 'create';

    /** @var int|null */
    public ?int $editingUserId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $is_super_admin = false;

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

    public function openCreateModal(): void
    {
        $this->userModalMode = 'create';
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_super_admin = false;
        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function openEditModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->userModalMode = 'edit';
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_super_admin = (bool) $user->is_super_admin;
        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function closeUserModal(): void
    {
        $this->showUserModal = false;
        $this->resetValidation();
    }

    public function saveUser(): void
    {
        if ($this->userModalMode === 'create') {
            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'is_super_admin' => ['boolean'],
            ]);
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'is_super_admin' => $this->is_super_admin,
            ]);
            session()->flash('status', "User {$this->name} created successfully.");
        } else {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->editingUserId],
                'is_super_admin' => ['boolean'],
            ];
            if (strlen($this->password) > 0) {
                $rules['password'] = ['string', 'min:8', 'confirmed'];
            }
            $this->validate($rules);
            $user = User::findOrFail($this->editingUserId);
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'is_super_admin' => $this->is_super_admin,
            ];
            if (strlen($this->password) >= 8) {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
            session()->flash('status', "User {$user->name} updated successfully.");
        }
        $this->closeUserModal();
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

        {{-- Toolbar: search + Add user --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="relative max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search users…"
                       class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="button" wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add user
            </button>
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
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
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
                            <td class="px-4 py-4 text-right">
                                <button type="button" wire:click="openEditModal({{ $user->id }})"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">
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

        {{-- User create/edit modal --}}
        @if($showUserModal)
            <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" aria-modal="true" role="dialog">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeUserModal" aria-hidden="true"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-xl w-full sm:max-w-md transform transition-all"
                         wire:click.stop>
                        <form wire:submit="saveUser" class="p-6 space-y-5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900">
                                    {{ $userModalMode === 'create' ? 'Add user' : 'Edit user' }}
                                </h3>
                                <button type="button" wire:click="closeUserModal" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div>
                                <label for="user-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" id="user-name" wire:model="name" required
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="user-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="user-email" wire:model="email" required
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror">
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="user-password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password {{ $userModalMode === 'edit' ? '(leave blank to keep current)' : '' }}
                                </label>
                                <input type="password" id="user-password" wire:model="password"
                                       {{ $userModalMode === 'create' ? 'required' : '' }}
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-500 @enderror"
                                       autocomplete="new-password">
                                @error('password')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if($userModalMode === 'create')
                                <div>
                                    <label for="user-password-confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                                    <input type="password" id="user-password-confirm" wire:model="password_confirmation"
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                           autocomplete="new-password">
                                </div>
                            @else
                                <div>
                                    <label for="user-password-confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                                    <input type="password" id="user-password-confirm" wire:model="password_confirmation"
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                           autocomplete="new-password">
                                </div>
                            @endif

                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="user-super-admin" wire:model="is_super_admin"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="user-super-admin" class="text-sm font-medium text-gray-700">Super admin</label>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" wire:click="closeUserModal"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                                    {{ $userModalMode === 'create' ? 'Create user' : 'Save changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
