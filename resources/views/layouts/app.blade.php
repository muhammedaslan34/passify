<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('storage/Favicon.png') }}" type="image/png">
        <link rel="manifest" href="/manifest.json">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Passify">
        <meta name="theme-color" content="#dc2626">
        <link rel="apple-touch-icon" href="{{ asset('storage/logo.png') }}">
        <script>
            (function () {
                const savedTheme = localStorage.getItem('passify:theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = savedTheme === 'light' || savedTheme === 'dark'
                    ? savedTheme
                    : (prefersDark ? 'dark' : 'light');

                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.style.colorScheme = theme;
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{
                sidebarCollapsed: JSON.parse(localStorage.getItem('passify:sidebar-collapsed') ?? 'false'),
                mobileSidebarOpen: false,
                theme: localStorage.getItem('passify:theme') ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
                applyTheme(value) {
                    document.documentElement.classList.toggle('dark', value === 'dark');
                    document.documentElement.style.colorScheme = value;
                    localStorage.setItem('passify:theme', value);
                    document.querySelector('meta[name=\'theme-color\']')?.setAttribute('content', value === 'dark' ? '#991b1b' : '#dc2626');
                },
                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                },
            }"
            x-init="
                applyTheme(theme);
                $watch('sidebarCollapsed', value => localStorage.setItem('passify:sidebar-collapsed', JSON.stringify(value)));
                $watch('theme', value => applyTheme(value));
            "
            class="min-h-screen min-h-[100dvh] max-w-[100vw] overflow-x-hidden bg-gray-100 text-gray-900 dark:bg-slate-950 dark:text-slate-100">
            @auth
                <div class="min-h-screen min-h-[100dvh] bg-slate-50 dark:bg-slate-950">
                    <livewire:layout.navigation />

                    <div class="min-h-screen min-w-0 flex-1 transition-all duration-300"
                         :class="sidebarCollapsed ? 'md:pl-24' : 'md:pl-72'">
                        <div class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
                            <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                                <div class="flex items-center">
                                    <button type="button"
                                            x-on:click="window.innerWidth >= 768 ? sidebarCollapsed = !sidebarCollapsed : mobileSidebarOpen = true"
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                                        <span class="sr-only">Toggle sidebar</span>
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="button"
                                            x-on:click="toggleTheme()"
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                                        <span class="sr-only">Toggle theme</span>
                                        <svg x-cloak x-show="theme === 'light'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.864 6.364l-1.06-1.06M6.697 6.697l-1.06-1.06m12.727 0l-1.06 1.06M6.697 17.303l-1.06 1.06M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                                        </svg>
                                        <svg x-cloak x-show="theme === 'dark'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0112 21.75 9.75 9.75 0 1118.998 2.248 7.5 7.5 0 0021.75 12c0 1.045-.213 2.04-.598 2.91z"/>
                                        </svg>
                                    </button>

                                    <a href="{{ route('profile') }}" wire:navigate
                                       class="inline-flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-slate-800 dark:text-indigo-300">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                            </svg>
                                        </div>
                                        <div class="hidden text-left sm:block">
                                            <p class="max-w-40 truncate font-semibold text-gray-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                                            <p class="max-w-40 truncate text-xs text-gray-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <main class="pb-20 md:pb-0">
                            @if (isset($header))
                                <div class="px-4 pt-6 sm:px-6 lg:px-8">
                                    {{ $header }}
                                </div>
                            @endif

                            {{ $slot }}
                        </main>
                    </div>
                </div>

                <livewire:layout.bottom-nav />
            @else
                <main>
                    {{ $slot }}
                </main>
            @endauth
        </div>
        <!-- PWA install banner -->
        <div id="pwa-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:12px 16px 16px;background:white;border-top:1px solid #e5e7eb;box-shadow:0 -4px 24px rgba(0,0,0,.10);font-family:system-ui,sans-serif;">
            <div style="display:flex;align-items:center;gap:12px;max-width:480px;margin:0 auto;">
                <img src="{{ asset('storage/logo.png') }}" width="44" height="44" style="border-radius:10px;flex-shrink:0;" alt="Passify">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:14px;color:#111827;">Install Passify</div>
                    <div id="pwa-subtitle" style="font-size:12px;color:#6b7280;margin-top:1px;">Add to your home screen for quick access</div>
                </div>
                <button id="pwa-install-btn" style="display:none;background:#dc2626;color:white;border:none;border-radius:9px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;flex-shrink:0;">Install</button>
                <button id="pwa-dismiss-btn" style="background:none;border:1px solid #e5e7eb;color:#6b7280;border-radius:9px;padding:8px 14px;font-size:13px;cursor:pointer;flex-shrink:0;">Not now</button>
            </div>
            <!-- iOS instruction -->
            <div id="pwa-ios-hint" style="display:none;max-width:480px;margin:10px auto 0;background:#f9fafb;border-radius:10px;padding:10px 12px;font-size:12px;color:#374151;line-height:1.5;">
                Tap <strong>Share</strong> <span style="font-size:15px;">⎙</span> then <strong>"Add to Home Screen"</strong> <span style="font-size:14px;">＋</span>
            </div>
        </div>

        <script>
        (function () {
            const DISMISSED_KEY = 'passify_pwa_dismissed';
            if (localStorage.getItem(DISMISSED_KEY)) return;

            const banner     = document.getElementById('pwa-banner');
            const installBtn = document.getElementById('pwa-install-btn');
            const dismissBtn = document.getElementById('pwa-dismiss-btn');
            const iosHint    = document.getElementById('pwa-ios-hint');
            const subtitle   = document.getElementById('pwa-subtitle');

            const isIOS     = /iphone|ipad|ipod/i.test(navigator.userAgent);
            const isMobile  = isIOS || /android/i.test(navigator.userAgent);
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;

            if (!isMobile || isStandalone) return;

            let deferredPrompt = null;

            if (isIOS) {
                // iOS: show immediately with manual instruction
                banner.style.display = 'block';
                iosHint.style.display = 'block';
                subtitle.style.display = 'none';
            } else {
                // Android: wait for browser's beforeinstallprompt
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    deferredPrompt = e;
                    banner.style.display = 'block';
                    installBtn.style.display = 'block';
                });

                installBtn.addEventListener('click', () => {
                    if (!deferredPrompt) return;
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(() => {
                        deferredPrompt = null;
                        banner.style.display = 'none';
                        localStorage.setItem(DISMISSED_KEY, '1');
                    });
                });
            }

            dismissBtn.addEventListener('click', () => {
                banner.style.display = 'none';
                localStorage.setItem(DISMISSED_KEY, '1');
            });

            // Register service worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        })();
        </script>
    </body>
</html>
