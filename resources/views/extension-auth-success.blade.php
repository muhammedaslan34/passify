<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="passify-extension-token" content="{{ $token }}">
    <title>Connecting — {{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 2.5rem 2rem; max-width: 360px; width: 100%; text-align: center; }
        .icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
        .icon-success { background: #dcfce7; }
        .icon-success svg { color: #16a34a; }
        .icon-waiting { background: #eef2ff; }
        .icon-waiting svg { color: #4f46e5; }
        h1 { font-size: 1.15rem; font-weight: 700; color: #111827; margin: 0 0 0.5rem; }
        p { font-size: 0.875rem; color: #6b7280; margin: 0; line-height: 1.5; }
        svg { width: 28px; height: 28px; }
    </style>
</head>
<body>
    <div class="card" id="card-waiting">
        <div class="icon icon-waiting">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
            </svg>
        </div>
        <h1>Connecting to extension…</h1>
        <p>This tab will close automatically once the extension is connected.</p>
    </div>

    <div class="card" id="card-success" style="display:none">
        <div class="icon icon-success">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1>Extension connected!</h1>
        <p>You can close this tab now.</p>
    </div>

    <script>
        // Signal to the Passify content script that the token is ready
        document.dispatchEvent(new CustomEvent('passify:token-ready'));
    </script>
</body>
</html>
