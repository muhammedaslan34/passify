<?php

use App\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Organization $organization;

    public string $name = '';
    public string $website_url = '';
    public string $description = '';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->authorize('update', $organization);

        $this->name        = $organization->name;
        $this->website_url = $organization->website_url ?? '';
        $this->description = $organization->description ?? '';
    }

    public function save(): void
    {
        $this->authorize('update', $this->organization);

        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->organization->update($validated);

        session()->flash('status', 'Settings saved.');
    }

    public function deleteOrganization(): void
    {
        $this->authorize('delete', $this->organization);
        $this->organization->delete();
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Organization Settings</h2>
            <p class="text-sm text-gray-500">{{ $organization->name }}</p>
        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        {{-- General Settings --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-base font-semibold text-gray-900">General</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-5">
                <div>
                    <x-input-label for="name" value="Organization Name *"/>
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required/>
                    <x-input-error :messages="$errors->get('name')" class="mt-2"/>
                </div>

                <div>
                    <x-input-label for="website_url" value="Website URL"/>
                    <x-text-input wire:model="website_url" id="website_url" type="url" class="mt-1 block w-full" placeholder="https://"/>
                    <x-input-error :messages="$errors->get('website_url')" class="mt-2"/>
                </div>

                <div>
                    <x-input-label for="description" value="Description"/>
                    <textarea wire:model="description" id="description" rows="3"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm resize-none"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2"/>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('organizations.show', $organization) }}" wire:navigate>
                        <x-secondary-button type="button">Cancel</x-secondary-button>
                    </a>
                    <x-primary-button wire:loading.attr="disabled" wire:loading.class="opacity-75">
                        <span wire:loading.remove wire:target="save">Save Changes</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </x-primary-button>
                </div>
            </form>
        </div>

        {{-- Danger Zone --}}
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-red-100 bg-red-50/50">
                <h3 class="text-base font-semibold text-red-800">Danger Zone</h3>
            </div>
            <div class="p-6 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-800">Delete this organization</p>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Permanently deletes the organization and all its credentials. This cannot be undone.
                    </p>
                </div>
                <button wire:click="deleteOrganization"
                        wire:confirm="Delete {{ $organization->name }}? All credentials will be permanently lost."
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition shrink-0"
                        wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    Delete Organization
                </button>
            </div>
        </div>

    </div>
</div>
