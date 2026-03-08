<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="w-full max-w-sm bg-white rounded-2xl shadow-xl px-8 py-10 anim-up">

    {{-- Mobile logo --}}
    <div class="lg:hidden flex justify-center mb-10">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                </svg>
            </div>
            <span class="font-bold text-xl text-gray-900 tracking-tight">{{ config('app.name') }}</span>
        </div>
    </div>

    {{-- Heading --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Welcome back</h1>
        <p class="text-gray-500 text-sm mt-1">Sign in to access your credential vault</p>
    </div>

    {{-- Session Status / Error (e.g. session expired) --}}
    <x-auth-session-status class="mb-5" :status="session('status')" />
    @if (session('error'))
        <div class="mb-5 font-medium text-sm text-red-600">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-5" novalidate>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Email address
            </label>
            <input
                wire:model="form.email"
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-900 text-sm placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                       transition-colors duration-200"
            />
            <x-input-error :messages="$errors->get('form.email')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div x-data="{ show: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-gray-700">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                       class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors duration-200">
                        Forgot password?
                    </a>
                @endif
            </div>

            <div class="relative">
                <input
                    wire:model="form.password"
                    id="password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-200 text-gray-900 text-sm placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           transition-colors duration-200"
                />
                <button
                    type="button"
                    @click="show = !show"
                    :aria-label="show ? 'Hide password' : 'Show password'"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 transition-colors duration-200 cursor-pointer"
                >
                    {{-- Eye open --}}
                    <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{-- Eye slash --}}
                    <svg x-show="show" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('form.password')" class="mt-1.5" />
        </div>

        {{-- Remember Me --}}
        <label for="remember" class="flex items-center gap-2.5 cursor-pointer select-none w-fit">
            <input
                wire:model="form.remember"
                id="remember"
                type="checkbox"
                name="remember"
                class="w-4 h-4 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer"
            />
            <span class="text-sm text-gray-600">Keep me signed in</span>
        </label>

        {{-- Submit --}}
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl
                   hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                   transition-colors duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed min-h-[48px]"
        >
            <svg wire:loading wire:target="login" class="animate-spin w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in...</span>
        </button>

    </form>

    {{-- Register link --}}
    @if (Route::has('register'))
        <p class="mt-7 text-center text-sm text-gray-500">
            Don't have an account?
            <a href="{{ route('register') }}" wire:navigate
               class="font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-200">
                Create one
            </a>
        </p>
    @endif

</div>
