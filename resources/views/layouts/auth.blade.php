<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('storage/Favicon.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @keyframes fadeSlideUp {
                from { opacity: 0; transform: translateY(20px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            @keyframes fadeSlideLeft {
                from { opacity: 0; transform: translateX(-20px); }
                to   { opacity: 1; transform: translateX(0); }
            }
            .anim-up   { animation: fadeSlideUp  0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }
            .anim-left { animation: fadeSlideLeft 0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }

            @media (prefers-reduced-motion: reduce) {
                .anim-up, .anim-left { animation: none; }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">

            {{-- ─── Left Branding Panel (desktop only) ─────────────────────── --}}
            <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 to-violet-700 flex-col justify-between p-14 relative overflow-hidden">

                {{-- Decorative blobs --}}
                <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-12 w-[28rem] h-[28rem] rounded-full bg-white/5 pointer-events-none"></div>
                <div class="absolute top-1/2 -right-32 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>

                {{-- Logo --}}
                <div class="relative z-10 flex items-center gap-3 anim-left">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-xl tracking-tight">{{ config('app.name') }}</span>
                </div>

                {{-- Headline --}}
                <div class="relative z-10 anim-up" style="animation-delay: 0.1s">
                    <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                        Secure your<br>credentials,<br>all in one place.
                    </h1>
                    <p class="text-indigo-200 text-base leading-relaxed max-w-xs">
                        Manage access for every organization, every team member — without the chaos.
                    </p>

                    {{-- Feature list --}}
                    <ul class="mt-10 space-y-4">
                        <li class="flex items-center gap-3 anim-up" style="animation-delay: 0.2s">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                            <span class="text-indigo-100 text-sm">Organize credentials by workspace</span>
                        </li>
                        <li class="flex items-center gap-3 anim-up" style="animation-delay: 0.3s">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                            <span class="text-indigo-100 text-sm">Role-based access for every team member</span>
                        </li>
                        <li class="flex items-center gap-3 anim-up" style="animation-delay: 0.4s">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                            <span class="text-indigo-100 text-sm">Invite teammates via email in seconds</span>
                        </li>
                    </ul>
                </div>

                {{-- Footer --}}
                <p class="relative z-10 text-indigo-300 text-xs anim-up" style="animation-delay: 0.35s">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Built for teams.
                </p>
            </div>

            {{-- ─── Right Form Panel ─────────────────────────────────────────── --}}
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center bg-slate-50 px-6 py-12 sm:px-10">
                {{ $slot }}
            </div>

        </div>
    </body>
</html>
