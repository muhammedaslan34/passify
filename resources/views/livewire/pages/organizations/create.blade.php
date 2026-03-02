<?php

use App\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $name = '';
    public string $website_url = '';
    public string $description = '';

    public function save(): void
    {
        $this->authorize('create', Organization::class);

        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $org = Organization::create([
            'name'        => $validated['name'],
            'website_url' => $validated['website_url'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by'  => auth()->id(),
        ]);

        $org->members()->attach(auth()->id(), ['role' => 'owner']);

        $this->redirect(route('organizations.show', $org), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('organizations.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Organization</h2>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-base font-semibold text-gray-900">Organization Details</h3>
                <p class="text-sm text-gray-500 mt-0.5">Each organization represents a website or project whose credentials you want to manage.</p>
            </div>

            <form wire:submit="save" class="p-6 space-y-5">
                <div>
                    <x-input-label for="name" value="Organization Name *"/>
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" placeholder="e.g. My Startup, Client Website" required autofocus/>
                    <x-input-error :messages="$errors->get('name')" class="mt-2"/>
                </div>

                <div>
                    <x-input-label for="website_url" value="Website URL"/>
                    <x-text-input wire:model="website_url" id="website_url" type="url" class="mt-1 block w-full" placeholder="https://example.com"/>
                    <x-input-error :messages="$errors->get('website_url')" class="mt-2"/>
                </div>

                <div>
                    <x-input-label for="description" value="Description"/>
                    <textarea wire:model="description" id="description" rows="3"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm resize-none"
                              placeholder="Brief description of this organization..."></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2"/>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('organizations.index') }}" wire:navigate>
                        <x-secondary-button type="button">Cancel</x-secondary-button>
                    </a>
                    <x-primary-button wire:loading.attr="disabled" wire:loading.class="opacity-75">
                        <span wire:loading.remove wire:target="save">Create Organization</span>
                        <span wire:loading wire:target="save">Creating…</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
