<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="contents">
    <div x-cloak
         x-show="mobileSidebarOpen"
         x-transition.opacity
         class="fixed inset-0 z-50 md:hidden">
        <div class="absolute inset-0 bg-slate-950/45" x-on:click="mobileSidebarOpen = false"></div>

        <aside class="relative flex h-full w-[86vw] max-w-sm flex-col border-r border-gray-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-slate-800">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center" x-on:click="mobileSidebarOpen = false">
                    <x-application-logo class="h-14 w-auto" />
                </a>

                <button type="button"
                        x-on:click="mobileSidebarOpen = false"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                    <span class="sr-only">Close navigation</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-5">
                <div class="space-y-1">
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-gray-400 dark:text-slate-500">Main</p>

                    <a href="{{ route('dashboard') }}" wire:navigate x-on:click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('organizations.index') }}" wire:navigate x-on:click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('organizations.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                        </svg>
                        <span>Organizations</span>
                    </a>
                </div>

                @if(auth()->user()->isSuperAdmin())
                    <div class="mt-6 space-y-1">
                        <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-gray-400 dark:text-slate-500">Admin</p>

                        <a href="{{ route('admin.dashboard') }}" wire:navigate x-on:click="mobileSidebarOpen = false"
                           class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h7.5v7.5h-7.5v-7.5zm9 0h7.5v4.5h-7.5v-4.5zM12.75 9.75h7.5v10.5h-7.5v-10.5zm-9 3h7.5v7.5h-7.5v-7.5z"/>
                            </svg>
                            <span>Admin Dashboard</span>
                        </a>

                        <a href="{{ route('admin.service-types') }}" wire:navigate x-on:click="mobileSidebarOpen = false"
                           class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('admin.service-types') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h9m-9 4.5h6m-8.25 8.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>
                            </svg>
                            <span>Service Types</span>
                        </a>

                        <a href="{{ route('admin.users') }}" wire:navigate x-on:click="mobileSidebarOpen = false"
                           class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('admin.users') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.72 9.72 0 00-12 0M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Users</span>
                        </a>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-slate-800">
                <a href="{{ route('profile') }}" wire:navigate x-on:click="mobileSidebarOpen = false"
                   class="mb-2 flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    <span>Profile</span>
                </a>

                <button wire:click="logout"
                        class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium text-red-600 transition hover:bg-red-50">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </aside>
    </div>

    <aside class="hidden border-r border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900 md:fixed md:inset-y-0 md:left-0 md:z-40 md:flex"
           :class="sidebarCollapsed ? 'md:w-24' : 'md:w-72'">
        <div class="flex min-h-screen w-full flex-col">
            <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-5 dark:border-slate-800">
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="flex min-w-0 items-center overflow-hidden"
                   :class="sidebarCollapsed ? 'justify-center' : 'flex-1'">
                    <x-application-logo class="h-16 w-auto shrink-0" />
                </a>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-5">
                <div class="space-y-6">
                    <div class="space-y-1">
                        <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-gray-400 dark:text-slate-500"
                           x-show="!sidebarCollapsed" x-transition.opacity>
                            Main
                        </p>

                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition.opacity>Dashboard</span>
                        </a>

                        <a href="{{ route('organizations.index') }}" wire:navigate
                           class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('organizations.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition.opacity>Organizations</span>
                        </a>
                    </div>

                    @if(auth()->user()->isSuperAdmin())
                        <div class="space-y-1">
                            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-gray-400 dark:text-slate-500"
                               x-show="!sidebarCollapsed" x-transition.opacity>
                                Admin
                            </p>

                            <a href="{{ route('admin.dashboard') }}" wire:navigate
                               class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h7.5v7.5h-7.5v-7.5zm9 0h7.5v4.5h-7.5v-4.5zM12.75 9.75h7.5v10.5h-7.5v-10.5zm-9 3h7.5v7.5h-7.5v-7.5z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" x-transition.opacity>Admin Dashboard</span>
                            </a>

                            <a href="{{ route('admin.service-types') }}" wire:navigate
                               class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('admin.service-types') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h9m-9 4.5h6m-8.25 8.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" x-transition.opacity>Service Types</span>
                            </a>

                            <a href="{{ route('admin.users') }}" wire:navigate
                               class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('admin.users') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.72 9.72 0 00-12 0M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" x-transition.opacity>Users</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="border-t border-gray-200 p-3 dark:border-slate-800">
                <a href="{{ route('profile') }}" wire:navigate
                   class="mb-2 flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    <div class="min-w-0" x-show="!sidebarCollapsed" x-transition.opacity>
                        <p class="truncate font-semibold text-gray-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                    </div>
                </a>

                <button wire:click="logout"
                        class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium text-red-600 transition hover:bg-red-50">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-transition.opacity>Log Out</span>
                </button>
            </div>
        </div>
    </aside>
</div>
