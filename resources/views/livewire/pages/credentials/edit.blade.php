<?php

use App\Models\Credential;
use App\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Organization $organization;
    public Credential $credential;

    public string $service_type = '';
    public string $name = '';
    public string $website_url = '';
    public string $email = '';
    public string $password = '';
    public string $note = '';

    public function mount(Organization $organization, Credential $credential): void
    {
        $this->organization = $organization;
        $this->credential   = $credential;
        $this->authorize('update', $credential);

        $this->service_type = $credential->service_type;
        $this->name         = $credential->name;
        $this->website_url  = $credential->website_url ?? '';
        $this->email        = $credential->email ?? '';
        $this->password     = $credential->password;
        $this->note         = $credential->note ?? '';
    }

    public function serviceTypes(): array
    {
        return [
            'hosting'      => 'Hosting',
            'domain'       => 'Domain',
            'email'        => 'Email',
            'database'     => 'Database',
            'social_media' => 'Social Media',
            'analytics'    => 'Analytics',
            'other'        => 'Other',
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->credential);

        $validated = $this->validate([
            'service_type' => ['required', 'in:hosting,domain,email,database,social_media,analytics,other'],
            'name'         => ['required', 'string', 'max:255'],
            'website_url'  => ['nullable', 'url', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'password'     => ['required', 'string', 'max:1000'],
            'note'         => ['nullable', 'string', 'max:2000'],
        ]);

        $this->credential->update($validated);

        session()->flash('status', 'Credential updated.');

        $this->redirect(route('organizations.show', $this->organization), navigate: true);
    }

    public function deleteCredential(): void
    {
        $this->authorize('delete', $this->credential);
        $this->credential->delete();

        session()->flash('status', 'Credential deleted.');

        $this->redirect(route('organizations.show', $this->organization), navigate: true);
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Credential</h2>
            <p class="text-sm text-gray-500">{{ $organization->name }}</p>
        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-base font-semibold text-gray-900">Edit: {{ $credential->name }}</h3>
            </div>

            <form wire:submit="save" class="p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <x-input-label for="service_type" value="Service Type *"/>
                        <select wire:model="service_type" id="service_type"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach($this->serviceTypes() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('service_type')" class="mt-2"/>
                    </div>

                    <div>
                        <x-input-label for="name" value="Display Name *"/>
                        <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required/>
                        <x-input-error :messages="$errors->get('name')" class="mt-2"/>
                    </div>
                </div>

                <div>
                    <x-input-label for="website_url" value="Login URL"/>
                    <x-text-input wire:model="website_url" id="website_url" type="url" class="mt-1 block w-full" placeholder="https://"/>
                    <x-input-error :messages="$errors->get('website_url')" class="mt-2"/>
                </div>

                <div>
                    <x-input-label for="email" value="Email / Username"/>
                    <x-text-input wire:model="email" id="email" type="text" class="mt-1 block w-full"/>
                    <x-input-error :messages="$errors->get('email')" class="mt-2"/>
                </div>

                <div x-data="{ show: false }">
                    <x-input-label for="password" value="Password *"/>
                    <div class="relative mt-1">
                        <x-text-input wire:model="password" id="password"
                                      x-bind:type="show ? 'text' : 'password'"
                                      class="block w-full pr-10 font-mono" required/>
                        <button type="button" x-on:click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-500 transition">
                            <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                </div>

                <div>
                    <x-input-label for="note" value="Notes"/>
                    <textarea wire:model="note" id="note" rows="3"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm resize-none"></textarea>
                    <x-input-error :messages="$errors->get('note')" class="mt-2"/>
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
            <div class="p-6 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-800">Delete this credential</p>
                    <p class="text-sm text-gray-500">This action is permanent and cannot be undone.</p>
                </div>
                <button wire:click="deleteCredential"
                        wire:confirm="Delete '{{ $credential->name }}'? This cannot be undone."
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition shrink-0"
                        wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    Delete Credential
                </button>
            </div>
        </div>

    </div>
</div>
