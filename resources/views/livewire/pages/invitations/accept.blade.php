<?php

use App\Models\Invitation;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $token = '';
    public ?Invitation $invitation = null;
    public string $errorState = '';  // 'not_found' | 'expired' | 'accepted'

    public function mount(string $token): void
    {
        $this->token = $token;

        $invitation = Invitation::where('token', $token)
            ->with('organization')
            ->first();

        if (!$invitation) {
            $this->errorState = 'not_found';
            return;
        }

        if ($invitation->isAccepted()) {
            $this->errorState = 'accepted';
            return;
        }

        if ($invitation->isExpired()) {
            $this->errorState = 'expired';
            return;
        }

        $this->invitation = $invitation;

        // If user is logged in and already a member, auto-redirect
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->belongsToOrganization($invitation->organization)) {
                $this->errorState = 'already_member';
                return;
            }
        }

        // If the user just registered and a token is stored in session, process it
        if (auth()->check() && session('invitation_token') === $token) {
            $this->accept();
        }
    }

    public function accept(): void
    {
        if (!$this->invitation || $this->errorState) {
            return;
        }

        if (!auth()->check()) {
            // Store token in session and redirect to register
            session(['invitation_token' => $this->token, 'invitation_email' => $this->invitation->email]);
            $this->redirect(route('register'), navigate: true);
            return;
        }

        $user = auth()->user();
        $org  = $this->invitation->organization;

        if (!$user->belongsToOrganization($org)) {
            $org->members()->attach($user->id, ['role' => $this->invitation->role]);
        }

        $this->invitation->markAsAccepted();
        session()->forget('invitation_token');

        $this->redirect(route('organizations.show', $org), navigate: true);
    }
}; ?>

<div class="flex flex-col items-center gap-6">
    {{-- Logo / Header --}}
    <div class="text-center">
        <div class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Passify</h1>
    </div>

    <div class="w-full">

        {{-- Error: not found --}}
        @if($errorState === 'not_found')
            <div class="text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-800">Invalid Invitation</h2>
                <p class="text-sm text-gray-500">This invitation link is invalid or does not exist.</p>
                <a href="{{ route('login') }}" wire:navigate class="inline-block text-sm text-indigo-600 hover:underline">Back to login</a>
            </div>

        {{-- Error: already accepted --}}
        @elseif($errorState === 'accepted')
            <div class="text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-800">Already Accepted</h2>
                <p class="text-sm text-gray-500">This invitation has already been used.</p>
                <a href="{{ route('organizations.index') }}" wire:navigate class="inline-block text-sm text-indigo-600 hover:underline">Go to my organizations</a>
            </div>

        {{-- Error: expired --}}
        @elseif($errorState === 'expired')
            <div class="text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-800">Invitation Expired</h2>
                <p class="text-sm text-gray-500">This invitation link has expired. Ask the organization owner to send a new one.</p>
                <a href="{{ route('login') }}" wire:navigate class="inline-block text-sm text-indigo-600 hover:underline">Back to login</a>
            </div>

        {{-- Error: already member --}}
        @elseif($errorState === 'already_member')
            <div class="text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-800">Already a Member</h2>
                <p class="text-sm text-gray-500">You are already a member of this organization.</p>
                @if($invitation)
                    <a href="{{ route('organizations.show', $invitation->organization) }}" wire:navigate
                       class="inline-block text-sm text-indigo-600 hover:underline">
                        Go to {{ $invitation->organization->name }}
                    </a>
                @endif
            </div>

        {{-- Valid invitation --}}
        @elseif($invitation)
            <div class="space-y-5">
                <div class="text-center">
                    <h2 class="text-lg font-bold text-gray-900">You're Invited!</h2>
                    <p class="text-sm text-gray-500 mt-1">You've been invited to join an organization on Passify.</p>
                </div>

                {{-- Invitation Card --}}
                <div class="bg-indigo-50 rounded-xl p-4 space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shrink-0">
                            {{ strtoupper(substr($invitation->organization->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $invitation->organization->name }}</p>
                            @if($invitation->organization->website_url)
                                <p class="text-xs text-gray-500">{{ $invitation->organization->website_url }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <span>Role:</span>
                        <span class="font-semibold px-2 py-0.5 rounded-full text-xs {{ $invitation->role === 'owner' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($invitation->role) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400">Expires {{ $invitation->expires_at->diffForHumans() }}</p>
                </div>

                @if(auth()->check())
                    {{-- Logged-in: accept button --}}
                    <div class="space-y-3">
                        <p class="text-sm text-center text-gray-600">
                            Accepting as <strong>{{ auth()->user()->name }}</strong>
                        </p>
                        <button wire:click="accept"
                                wire:loading.attr="disabled"
                                class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                            <span wire:loading.remove>Accept Invitation</span>
                            <span wire:loading>Joining…</span>
                        </button>
                        <p class="text-xs text-center text-gray-400">
                            Not you?
                            <button wire:click="$dispatch('logout')" class="text-indigo-600 hover:underline">Sign in with another account</button>
                        </p>
                    </div>
                @else
                    {{-- Guest: login or register --}}
                    <div class="space-y-3">
                        <p class="text-sm text-center text-gray-600">To accept this invitation, you need a Passify account.</p>
                        <button wire:click="accept"
                                class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
                            Create Account & Join
                        </button>
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                            <div class="relative text-center"><span class="px-3 bg-white text-xs text-gray-400">or</span></div>
                        </div>
                        <a href="{{ route('login') }}?invitation={{ $token }}" wire:navigate
                           class="block w-full py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition text-center">
                            Sign In to Accept
                        </a>
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>
