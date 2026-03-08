# Passify — Chrome Extension, PWA & Password Generator Design

**Date:** 2026-03-07
**Status:** Approved

---

## Overview

Three new surfaces added to Passify:

1. **Chrome Extension (Manifest V3)** — auto-detect & save credentials from login forms, autofill saved credentials, password generator in popup
2. **PWA** — standalone mobile web app consuming the Passify API, full CRUD, installable on Android/iOS
3. **Password Generator** — shared pure JS module used across web app, extension, and PWA

All surfaces talk to a new Laravel Sanctum REST API layer added alongside the existing Livewire web app.

---

## Architecture

```
passify/
├── app/                          # Laravel (existing)
├── resources/views/              # Livewire (existing)
├── routes/
│   ├── web.php                   # existing
│   └── api.php                   # NEW — Sanctum token API
├── chrome-extension/             # NEW — Manifest V3 extension
│   ├── manifest.json
│   ├── popup/                    # popup UI (generator + credential search + settings)
│   ├── content/                  # content scripts (form detection + autofill)
│   └── background/               # service worker (OAuth redirect handler)
└── pwa/                          # NEW — standalone mobile PWA (Vue 3 + Vite)
    ├── index.html
    ├── manifest.json
    ├── sw.js                     # service worker (offline app shell cache)
    └── src/
        ├── views/                # Orgs, Generator, Search, Profile screens
        ├── api.js                # API client (bearer token)
        └── password-generator.js # shared module (copied from chrome-extension/)
```

**Shared module:** `password-generator.js` is a zero-dependency pure JS file. It lives in `chrome-extension/` and is copied into `pwa/src/` at build time. The web app (Livewire) loads it as a Vite asset.

---

## REST API

All routes under `/api/` prefix, protected by `auth:sanctum` middleware.

```
POST   /api/auth/token                                  # login → bearer token
DELETE /api/auth/token                                  # logout (revoke token)

GET    /api/organizations                               # list user's orgs
GET    /api/organizations/{org}                         # org detail
GET    /api/organizations/{org}/credentials             # list credentials
POST   /api/organizations/{org}/credentials             # create credential
PUT    /api/organizations/{org}/credentials/{cred}      # update credential
DELETE /api/organizations/{org}/credentials/{cred}      # delete credential

GET    /api/credentials/search?url=&q=                  # search by URL or name (autofill)
POST   /api/password/generate                           # generate password server-side (optional)
```

**Auth for Chrome Extension — OAuth-style redirect flow:**
1. User clicks "Login" in popup → extension opens `https://passify.app/extension/auth` in a new tab
2. User completes Laravel login form
3. Laravel generates a Sanctum token and redirects to `passify-extension://auth?token=xxx`
4. Extension background service worker intercepts the redirect via `chrome.webRequest` and stores token in `chrome.storage.local`
5. Tab closes automatically

**Sanctum config:** stateless token auth only (`api` guard, no session cookies). Tokens stored in `personal_access_tokens` table.

---

## Chrome Extension

### Manifest V3 structure

- **`manifest.json`** — declares permissions: `storage`, `activeTab`, `webRequest`, `tabs`, host permissions for the Passify domain
- **`popup/`** — 3 tabs:
  - Password Generator (main tab)
  - Search credentials by name or URL
  - Settings (login/logout, server URL config)
- **`content/content.js`** — injected on all `http`/`https` pages:
  - On load: detects `<input type="password">` fields; if a credential matches current URL, shows "Autofill?" banner
  - On form submit: captures email field + password field + `window.location.href`; shows "Save to Passify?" banner with org selector
  - All API calls proxied through background service worker (avoids CORS)
- **`background/background.js`** — Manifest V3 service worker:
  - Intercepts `passify-extension://auth?token=xxx` redirect
  - Stores token in `chrome.storage.local`
  - Handles fetch requests from content script (message passing)

### Popup UI layout

```
┌──────────────────────────────┐
│  [Generator] [Search] [...]  │  ← tabs
├──────────────────────────────┤
│  Very Strong          ████░  │  ← strength meter
│                              │
│  Length: [────●────] 16      │  ← range slider
│                              │
│  ☑ Lowercase (abc)           │
│  ☑ Uppercase (ABC)           │
│  ☑ Numbers (123)             │
│  ☑ Symbols (!#$)             │
│                              │
│  ┌──────────────────────┐    │
│  │ aB3$kL9#mN2!xQ7      │ 📋 │  ← generated password + copy
│  └──────────────────────┘    │
│        [Generate]            │
└──────────────────────────────┘
```

---

## PWA (Mobile)

**Stack:** Vue 3 + Vite, served as static files from `/pwa/dist/`. Deployed alongside Laravel (or separately via CDN).

**4 screens, bottom nav:**

| Tab | Screen | Description |
|-----|---------|-------------|
| Orgs | Organization list | Tap org → credential list → tap credential → detail with reveal/copy |
| Generator | Password generator | Same UI as extension popup |
| Search | Global search | Search credentials by name or URL across all orgs |
| Profile | Account | Login/logout, token info |

**PWA manifest (`pwa/manifest.json`):**
- `display: standalone` — feels native on Android/iOS
- App name: "Passify"
- Icons: 192x192, 512x512

**Service worker (`pwa/sw.js`):**
- Caches app shell (HTML, CSS, JS) for offline launch
- Credential data is NOT cached (security — no offline credential access)
- Network-first strategy for all API calls

**Login:** Email + password form → `POST /api/auth/token` → token stored in `localStorage`.

---

## Password Generator Module

**File:** `password-generator.js` — zero dependencies, framework-agnostic.

```js
// API
generate({ length, lowercase, uppercase, numbers, symbols }) → string
strength(password) → { score: 0-4, label: string, color: string }

// Strength labels
0 → 'Very Weak'   (red)
1 → 'Weak'        (orange)
2 → 'Fair'        (yellow)
3 → 'Strong'      (light green)
4 → 'Very Strong' (dark green)
```

Strength scoring: based on length, character set variety, and estimated entropy (bits). No external library — keeps extension lightweight.

**Integration per surface:**

| Surface | Integration |
|---------|-------------|
| Web app (Livewire) | Alpine.js component; "Generate Password" button on credential create/edit opens a modal |
| Chrome extension | Popup Generator tab — standalone UI |
| PWA | Generator screen — standalone UI |

**Web app modal** appears in `credentials/create.blade.php` and `credentials/edit.blade.php`. User configures options → clicks "Use this password" → password field is populated.

---

## Implementation Phases

1. **Sanctum API** — install Sanctum, create `api.php` routes, API controllers, CORS config
2. **Password Generator module** — pure JS `password-generator.js` + web app modal integration
3. **Chrome Extension** — scaffold Manifest V3, background + content scripts, popup UI
4. **PWA** — scaffold Vue 3 + Vite in `/pwa/`, implement 4 screens, service worker, manifest
