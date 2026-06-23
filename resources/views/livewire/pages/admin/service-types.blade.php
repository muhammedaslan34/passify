<?php

use App\Models\ServiceType;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showServiceTypeModal = false;
    public string $serviceTypeModalMode = 'create';
    public ?int $editingServiceTypeId = null;

    public string $slug = '';
    public string $name = '';
    public string $color = 'gray';
    public int $sort_order = 0;
    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceType::query()
            ->when($this->search, fn($query) => $query
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('slug', 'like', '%' . $this->search . '%'))
            ->withCount('credentials')
            ->ordered()
            ->paginate(20);
    }

    public function colorOptions(): array
    {
        return ServiceType::colorOptions()->all();
    }

    public function openCreateModal(): void
    {
        $this->serviceTypeModalMode = 'create';
        $this->editingServiceTypeId = null;
        $this->slug = '';
        $this->name = '';
        $this->color = 'gray';
        $this->sort_order = (int) (ServiceType::max('sort_order') ?? 0) + 1;
        $this->is_active = true;
        $this->resetValidation();
        $this->showServiceTypeModal = true;
    }

    public function openEditModal(int $serviceTypeId): void
    {
        $serviceType = ServiceType::findOrFail($serviceTypeId);

        $this->serviceTypeModalMode = 'edit';
        $this->editingServiceTypeId = $serviceType->id;
        $this->slug = $serviceType->slug;
        $this->name = $serviceType->name;
        $this->color = $serviceType->color;
        $this->sort_order = $serviceType->sort_order;
        $this->is_active = (bool) $serviceType->is_active;
        $this->resetValidation();
        $this->showServiceTypeModal = true;
    }

    public function closeServiceTypeModal(): void
    {
        $this->showServiceTypeModal = false;
        $this->resetValidation();
    }

    public function saveServiceType(): void
    {
        $slug = Str::of($this->slug)->trim()->lower()->value();

        $validated = validator([
            'slug' => $slug,
            'name' => $this->name,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ], [
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                'lowercase',
                Rule::unique('service_types', 'slug')->ignore($this->editingServiceTypeId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types', 'name')->ignore($this->editingServiceTypeId),
            ],
            'color' => ['required', Rule::in(array_keys($this->colorOptions()))],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ])->validate();

        $this->slug = $validated['slug'];

        if ($this->serviceTypeModalMode === 'create') {
            ServiceType::create($validated);
            session()->flash('status', "Service type {$validated['name']} created.");
        } else {
            $serviceType = ServiceType::findOrFail($this->editingServiceTypeId);
            $serviceType->update($validated);
            session()->flash('status', "Service type {$validated['name']} updated.");
        }

        $this->closeServiceTypeModal();
    }

    public function deleteServiceType(int $serviceTypeId): void
    {
        $serviceType = ServiceType::withCount('credentials')->findOrFail($serviceTypeId);

        if ($serviceType->credentials_count > 0) {
            session()->flash('error', "Service type {$serviceType->name} is in use and cannot be deleted.");

            return;
        }

        $serviceType->delete();

        session()->flash('status', "Service type {$serviceType->name} deleted.");
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Service Types</h2>
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
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zm9-3.758a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="relative max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search service types…"
                       class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <button type="button" wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add service type
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/70">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Slug</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Credentials</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Sort</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->serviceTypes as $serviceType)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $serviceType->badgeClasses() }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $serviceType->dotClasses() }}"></span>
                                        {{ $serviceType->name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 hidden sm:table-cell">{{ $serviceType->slug }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700 text-center">{{ $serviceType->credentials_count }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $serviceType->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $serviceType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 text-center hidden md:table-cell">{{ $serviceType->sort_order }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="openEditModal({{ $serviceType->id }})"
                                            class="text-xs font-semibold px-2.5 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="deleteServiceType({{ $serviceType->id }})"
                                            wire:confirm="Delete {{ $serviceType->name }}? This cannot be undone."
                                            class="text-xs font-semibold px-2.5 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">
                                No service types found{{ $search ? ' matching "' . $search . '"' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($this->serviceTypes->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $this->serviceTypes->links() }}
                </div>
            @endif
        </div>

        @if($showServiceTypeModal)
            <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" aria-modal="true" role="dialog">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeServiceTypeModal" aria-hidden="true"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-xl w-full sm:max-w-lg transform transition-all"
                         wire:click.stop>
                        <form wire:submit="saveServiceType" class="p-6 space-y-5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900">
                                    {{ $serviceTypeModalMode === 'create' ? 'Add service type' : 'Edit service type' }}
                                </h3>
                                <button type="button" wire:click="closeServiceTypeModal" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="service-type-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input type="text" id="service-type-name" wire:model="name" required
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
                                    @error('name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="service-type-slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                                    <input type="text" id="service-type-slug" wire:model="slug" required
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('slug') border-red-500 @enderror"
                                           placeholder="social_media">
                                    @error('slug')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="service-type-color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                                    <select id="service-type-color" wire:model="color"
                                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('color') border-red-500 @enderror">
                                        @foreach($this->colorOptions() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('color')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="service-type-sort-order" class="block text-sm font-medium text-gray-700 mb-1">Sort order</label>
                                    <input type="number" id="service-type-sort-order" wire:model="sort_order" min="0"
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('sort_order') border-red-500 @enderror">
                                    @error('sort_order')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" wire:model="is_active"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">Active</span>
                            </label>

                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Preview</p>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ (new App\Models\ServiceType(['color' => $color]))->badgeClasses() }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ (new App\Models\ServiceType(['color' => $color]))->dotClasses() }}"></span>
                                    {{ $name ?: 'Service Type' }}
                                </span>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" wire:click="closeServiceTypeModal"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                                    {{ $serviceTypeModalMode === 'create' ? 'Create type' : 'Save changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
