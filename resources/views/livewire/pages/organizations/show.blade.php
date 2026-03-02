<?php

use App\Models\Credential;
use App\Models\Organization;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Organization $organization;

    public string $search = '';
    public string $filterType = '';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->authorize('view', $organization);
    }

    #[Computed]
    public function credentials()
    {
        return $this->organization->credentials()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->filterType, fn($q) => $q->where('service_type', $this->filterType))
            ->orderBy('name')
            ->get();
    }

    public function isOwner(): bool
    {
        return auth()->user()->isOwnerOfOrganization($this->organization)
            || auth()->user()->isSuperAdmin();
    }

    public function deleteCredential(Credential $credential): void
    {
        $this->authorize('delete', $credential);
        $credential->delete();
        session()->flash('status', 'Credential deleted.');
    }

    public function serviceTypeLabel(string $type): string
    {
        return match($type) {
            'hosting'      => 'Hosting',
            'domain'       => 'Domain',
            'email'        => 'Email',
            'database'     => 'Database',
            'social_media' => 'Social Media',
            'analytics'    => 'Analytics',
            default        => 'Other',
        };
    }

    public function serviceTypeBadge(string $type): string
    {
        return match($type) {
            'hosting'      => 'bg-blue-100 text-blue-700',
            'domain'       => 'bg-purple-100 text-purple-700',
            'email'        => 'bg-pink-100 text-pink-700',
            'database'     => 'bg-orange-100 text-orange-700',
            'social_media' => 'bg-cyan-100 text-cyan-700',
            'analytics'    => 'bg-emerald-100 text-emerald-700',
            default        => 'bg-gray-100 text-gray-600',
        };
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('organizations.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $organization->name }}</h2>
                @if($organization->website_url)
                    <a href="{{ $organization->website_url }}" target="_blank" rel="noopener" class="text-sm text-indigo-500 hover:underline">{{ $organization->website_url }}</a>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('organizations.members', $organization) }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                Members
            </a>
            @if($this->isOwner())
                <a href="{{ route('organizations.settings', $organization) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
                <a href="{{ route('credentials.create', $organization) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Credential
                </a>
            @endif
        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        {{-- Filter & Search --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Search --}}
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="search"
                           placeholder="Search credentials…"
                           class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                {{-- Service Type Filter --}}
                <div class="flex flex-wrap gap-1.5">
                    @foreach([''=>'All', 'hosting'=>'Hosting', 'domain'=>'Domain', 'email'=>'Email', 'database'=>'Database', 'social_media'=>'Social', 'analytics'=>'Analytics', 'other'=>'Other'] as $value => $label)
                        <button wire:click="$set('filterType', '{{ $value }}')"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $filterType === $value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Credentials List --}}
        @if($this->credentials->isEmpty())
            <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <p class="text-gray-500 mb-4">
                    @if($search || $filterType)
                        No credentials match your filter.
                    @else
                        No credentials stored yet.
                    @endif
                </p>
                @if($this->isOwner() && !$search && !$filterType)
                    <a href="{{ route('credentials.create', $organization) }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
                        Add First Credential
                    </a>
                @endif
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->credentials as $credential)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
                         x-data="{ showPass: false, copied: null }">
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 mt-0.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $this->serviceTypeBadge($credential->service_type) }}">
                                    {{ $this->serviceTypeLabel($credential->service_type) }}
                                </span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900 truncate">{{ $credential->name }}</h4>

                                @if($credential->website_url)
                                    <a href="{{ $credential->website_url }}" target="_blank" rel="noopener" class="text-xs text-indigo-500 hover:underline truncate block">{{ $credential->website_url }}</a>
                                @endif

                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    {{-- Email --}}
                                    @if($credential->email)
                                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 group">
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                            </svg>
                                            <span class="text-sm text-gray-700 truncate flex-1">{{ $credential->email }}</span>
                                            <button
                                                x-on:click="navigator.clipboard.writeText('{{ $credential->email }}'); copied = 'email'; setTimeout(() => copied = null, 2000)"
                                                class="shrink-0 text-gray-300 hover:text-indigo-500 transition"
                                                title="Copy email">
                                                <template x-if="copied === 'email'">
                                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                </template>
                                                <template x-if="copied !== 'email'">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/></svg>
                                                </template>
                                            </button>
                                        </div>
                                    @endif

                                    {{-- Password --}}
                                    <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 group">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                        </svg>
                                        <span class="text-sm text-gray-700 truncate flex-1 font-mono" x-show="showPass">{{ $credential->password }}</span>
                                        <span class="text-sm text-gray-400 tracking-widest flex-1" x-show="!showPass">••••••••</span>
                                        <button x-on:click="showPass = !showPass" class="shrink-0 text-gray-300 hover:text-indigo-500 transition" title="Toggle password">
                                            <svg x-show="!showPass" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <svg x-show="showPass" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                        </button>
                                        <button
                                            x-on:click="navigator.clipboard.writeText('{{ $credential->password }}'); copied = 'pass'; setTimeout(() => copied = null, 2000)"
                                            class="shrink-0 text-gray-300 hover:text-indigo-500 transition"
                                            title="Copy password">
                                            <template x-if="copied === 'pass'">
                                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </template>
                                            <template x-if="copied !== 'pass'">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/></svg>
                                            </template>
                                        </button>
                                    </div>
                                </div>

                                @if($credential->note)
                                    <p class="text-sm text-gray-500 mt-2 italic">{{ $credential->note }}</p>
                                @endif
                            </div>

                            {{-- Actions (owners only) --}}
                            @if($this->isOwner())
                                <div class="shrink-0 flex items-center gap-1">
                                    <a href="{{ route('credentials.edit', [$organization, $credential]) }}" wire:navigate
                                       class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </a>
                                    <button wire:click="deleteCredential({{ $credential->id }})"
                                            wire:confirm="Delete '{{ $credential->name }}'? This cannot be undone."
                                            class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
