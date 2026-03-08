<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
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
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Create an account</h1>
        <p class="text-gray-500 text-sm mt-1">Get started with your credential vault</p>
    </div>

    {{-- Session Status / Error --}}
    <x-auth-session-status class="mb-5" :status="session('status')" />
    @if (session('error'))
        <div class="mb-5 font-medium text-sm text-red-600">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="register" class="space-y-5" novalidate>

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Name
            </label>
            <input
                wire:model="name"
                id="name"
                type="text"
                name="name"
                required
                autofocus
                autocomplete="name"
                placeholder="Your name"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-900 text-sm placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                       transition-colors duration-200"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Email address
            </label>
            <input
                wire:model="email"
                id="email"
                type="email"
                name="email"
                required
                autocomplete="username"
                placeholder="you@example.com"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-900 text-sm placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                       transition-colors duration-200"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div x-data="{ show: false }">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Password
            </label>
            <div class="relative">
                <input
                    wire:model="password"
                    id="password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="new-password"
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
                    <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <svg x-show="show" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        {{-- Confirm Password --}}
        <div x-data="{ show: false }">
            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Confirm password
            </label>
            <div class="relative">
                <input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    :type="show ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
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
                    <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <svg x-show="show" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl
                   hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                   transition-colors duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed min-h-[48px]"
        >
            <svg wire:loading wire:target="register" class="animate-spin w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span wire:loading.remove wire:target="register">Create account</span>
            <span wire:loading wire:target="register">Creating account...</span>
        </button>

    </form>

    {{-- Login link --}}
    <p class="mt-7 text-center text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate
           class="font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-200">
            Sign in
        </a>
    </p>

</div>
