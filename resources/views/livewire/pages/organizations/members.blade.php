<?php

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Organization $organization;

    // Invite by email
    public string $inviteEmail = '';
    public string $inviteRole = 'member';

    // Add existing user manually
    public string $userSearch = '';
    public string $addRole = 'member';
    public $userResults = [];

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->authorize('view', $organization);
    }

    public function isOwner(): bool
    {
        return auth()->user()->isOwnerOfOrganization($this->organization)
            || auth()->user()->isSuperAdmin();
    }

    public function getInvitationsProperty()
    {
        return $this->organization->invitations()->orderByDesc('created_at')->get();
    }

    // ── Invite by email ────────────────────────────────────────────────────────

    public function inviteByEmail(): void
    {
        $this->authorize('update', $this->organization);

        $this->validate([
            'inviteEmail' => ['required', 'email', 'max:255'],
            'inviteRole'  => ['required', 'in:owner,member'],
        ]);

        // Check if already a member
        if ($this->organization->members()->where('email', $this->inviteEmail)->exists()) {
            $this->addError('inviteEmail', 'This user is already a member of this organization.');
            return;
        }

        // Check for active (non-expired, non-accepted) invitation
        $existing = $this->organization->invitations()
            ->where('email', $this->inviteEmail)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            $this->addError('inviteEmail', 'An active invitation already exists for this email.');
            return;
        }

        $this->organization->invitations()->create([
            'email'      => $this->inviteEmail,
            'role'       => $this->inviteRole,
            'token'      => Str::random(48),
            'expires_at' => now()->addDays(7),
        ]);

        session()->flash('status', "Invitation sent to {$this->inviteEmail}.");
        $this->inviteEmail = '';
        $this->inviteRole  = 'member';
    }

    public function revokeInvitation(Invitation $invitation): void
    {
        $this->authorize('update', $this->organization);
        $invitation->delete();
        session()->flash('status', 'Invitation revoked.');
    }

    // ── Add existing user manually ─────────────────────────────────────────────

    public function updatedUserSearch(): void
    {
        if (strlen($this->userSearch) < 2) {
            $this->userResults = [];
            return;
        }

        $memberIds = $this->organization->members()->pluck('users.id')->toArray();

        $this->userResults = User::where(function ($q) {
            $q->where('name', 'like', '%' . $this->userSearch . '%')
              ->orWhere('email', 'like', '%' . $this->userSearch . '%');
        })
        ->whereNotIn('id', $memberIds)
        ->take(5)
        ->get(['id', 'name', 'email'])
        ->toArray();
    }

    public function addUser(int $userId): void
    {
        $this->authorize('update', $this->organization);

        $user = User::findOrFail($userId);

        if ($this->organization->members()->where('user_id', $user->id)->exists()) {
            session()->flash('error', 'User is already a member.');
            return;
        }

        $this->organization->members()->attach($user->id, ['role' => $this->addRole]);

        session()->flash('status', "{$user->name} added as {$this->addRole}.");
        $this->userSearch  = '';
        $this->userResults = [];
    }

    // ── Member management ──────────────────────────────────────────────────────

    public function changeRole(int $userId, string $role): void
    {
        $this->authorize('update', $this->organization);

        if ($userId === auth()->id()) {
            session()->flash('error', 'You cannot change your own role.');
            return;
        }

        $this->organization->members()->updateExistingPivot($userId, ['role' => $role]);
        session()->flash('status', 'Role updated.');
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('update', $this->organization);

        if ($userId === auth()->id()) {
            session()->flash('error', 'Use "Leave Organization" to remove yourself.');
            return;
        }

        $this->organization->members()->detach($userId);
        session()->flash('status', 'Member removed.');
    }

    public function leaveOrganization(): void
    {
        $this->authorize('view', $this->organization);

        $user = auth()->user();

        // Block if sole owner
        $ownerCount = $this->organization->members()->wherePivot('role', 'owner')->count();
        if ($user->isOwnerOfOrganization($this->organization) && $ownerCount <= 1) {
            session()->flash('error', 'You are the sole owner. Transfer ownership or delete the organization first.');
            return;
        }

        $this->organization->members()->detach($user->id);
        $this->redirect(route('organizations.index'), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('organizations.show', $organization) }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Members</h2>
            <p class="text-sm text-gray-500">{{ $organization->name }}</p>
        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

        {{-- Current Members --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">
                    Members
                    <span class="ml-2 text-sm font-normal text-gray-400">({{ $organization->members->count() }})</span>
                </h3>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach($organization->members as $member)
                    <li class="px-6 py-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-sm shrink-0">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $member->name }}
                                @if($member->id === auth()->id()) <span class="text-gray-400 font-normal">(you)</span> @endif
                            </p>
                            <p class="text-xs text-gray-500 truncate">{{ $member->email }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($this->isOwner() && $member->id !== auth()->id())
                                <select wire:change="changeRole({{ $member->id }}, $event.target.value)"
                                        class="text-xs border border-gray-200 rounded-lg py-1 pl-2 pr-6 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                    <option value="owner" {{ $member->pivot->role === 'owner' ? 'selected' : '' }}>Owner</option>
                                </select>
                                <button wire:click="removeMember({{ $member->id }})"
                                        wire:confirm="Remove {{ $member->name }} from this organization?"
                                        class="text-gray-400 hover:text-red-600 transition p-1 rounded-lg hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
                                </button>
                            @else
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $member->pivot->role === 'owner' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($member->pivot->role) }}
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Leave org --}}
            @if(!$this->isOwner() || $organization->members()->wherePivot('role', 'owner')->count() > 1)
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
                    <button wire:click="leaveOrganization"
                            wire:confirm="Leave {{ $organization->name }}? You will lose access to all its credentials."
                            class="text-sm text-red-600 hover:text-red-800 font-medium transition">
                        Leave organization
                    </button>
                </div>
            @endif
        </div>

        @if($this->isOwner())
            {{-- Invite by Email --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-semibold text-gray-900">Invite by Email</h3>
                    <p class="text-sm text-gray-500 mt-0.5">An invitation link will be generated (valid for 7 days).</p>
                </div>
                <form wire:submit="inviteByEmail" class="p-6 flex flex-col sm:flex-row gap-3 items-start">
                    <div class="flex-1">
                        <x-text-input wire:model="inviteEmail" type="email" class="w-full" placeholder="colleague@example.com"/>
                        <x-input-error :messages="$errors->get('inviteEmail')" class="mt-2"/>
                    </div>
                    <select wire:model="inviteRole" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="member">Member</option>
                        <option value="owner">Owner</option>
                    </select>
                    <x-primary-button wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="inviteByEmail">Send Invite</span>
                        <span wire:loading wire:target="inviteByEmail">Sending…</span>
                    </x-primary-button>
                </form>

                @if($this->invitations->isNotEmpty())
                    <div class="border-t border-gray-100">
                        <div class="px-6 py-3 bg-gray-50/30">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pending Invitations</p>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach($this->invitations as $invitation)
                                <li class="px-6 py-3 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $invitation->email }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ ucfirst($invitation->role) }} ·
                                            @if($invitation->isAccepted())
                                                <span class="text-emerald-600">Accepted</span>
                                            @elseif($invitation->isExpired())
                                                <span class="text-red-500">Expired</span>
                                            @else
                                                Expires {{ $invitation->expires_at->diffForHumans() }}
                                            @endif
                                        </p>
                                    </div>
                                    @if(!$invitation->isAccepted())
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-mono select-all">
                                                {{ url('/invitations/' . $invitation->token . '/accept') }}
                                            </span>
                                            <button wire:click="revokeInvitation({{ $invitation->id }})"
                                                    wire:confirm="Revoke this invitation?"
                                                    class="text-gray-400 hover:text-red-600 transition p-1 rounded hover:bg-red-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Add Existing User --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-semibold text-gray-900">Add Existing User</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Search for registered users and add them directly — no email sent.</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                            <x-text-input wire:model.live="userSearch" type="search" class="w-full pl-9" placeholder="Search by name or email…"/>
                        </div>
                        <select wire:model="addRole" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="member">Member</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>

                    @if(!empty($userResults))
                        <ul class="border border-gray-200 rounded-xl divide-y divide-gray-100 overflow-hidden">
                            @foreach($userResults as $user)
                                <li class="flex items-center gap-3 px-4 py-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-semibold text-xs shrink-0">
                                        {{ strtoupper(substr($user['name'], 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $user['name'] }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $user['email'] }}</p>
                                    </div>
                                    <button wire:click="addUser({{ $user['id'] }})"
                                            class="text-xs font-semibold px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition shrink-0">
                                        Add
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @elseif(strlen($userSearch) >= 2)
                        <p class="text-sm text-gray-400 text-center py-3">No users found matching "{{ $userSearch }}".</p>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>
