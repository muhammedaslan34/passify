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
        <meta name="theme-color" content="#4f46e5">
        <link rel="apple-touch-icon" href="{{ asset('storage/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen min-h-[100dvh] bg-gray-100 overflow-x-hidden max-w-[100vw]">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content: extra bottom padding on mobile so content isn't hidden under fixed bottom nav -->
            <main class="pb-20 md:pb-0">
                {{ $slot }}
            </main>

            <!-- Mobile bottom navigation (md and up use top nav only) -->
            @auth
                <livewire:layout.bottom-nav />
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
                <button id="pwa-install-btn" style="display:none;background:#4f46e5;color:white;border:none;border-radius:9px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;flex-shrink:0;">Install</button>
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
