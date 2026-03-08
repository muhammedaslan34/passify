const DEFAULT_PASSIFY_URL = 'http://localhost:8000';

// Listen for OAuth redirect from extension auth page
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
    if (changeInfo.url && changeInfo.url.startsWith('passify-extension://auth')) {
        const urlStr = changeInfo.url.replace('passify-extension://', 'https://passify-extension/');
        const url = new URL(urlStr);
        const token = url.searchParams.get('token');
        if (token) {
            chrome.storage.local.set({ token }, () => {
                chrome.tabs.remove(tabId);
            });
        }
    }
});

// Proxy API calls from content scripts (avoids CORS issues)
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message.type === 'API_REQUEST') {
        chrome.storage.local.get(['token', 'passifyUrl'], ({ token, passifyUrl }) => {
            const base = passifyUrl || DEFAULT_PASSIFY_URL;
            fetch(`${base}/api${message.path}`, {
                method: message.method || 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: message.body ? JSON.stringify(message.body) : undefined,
            })
            .then(r => r.json())
            .then(data => sendResponse({ ok: true, data }))
            .catch(err => sendResponse({ ok: false, error: err.message }));
        });
        return true; // keep channel open for async response
    }

    if (message.type === 'SAVE_CREDENTIAL') {
        chrome.storage.local.get(['token', 'passifyUrl'], ({ token, passifyUrl }) => {
            const base = passifyUrl || DEFAULT_PASSIFY_URL;
            const headers = {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            };

            // Fetch user's organizations and save to first org where user is owner
            fetch(`${base}/api/organizations`, { headers })
                .then(r => r.json())
                .then(({ data: orgs }) => {
                    const ownerOrg = orgs?.find(o => o.role === 'owner');
                    if (!ownerOrg) {
                        sendResponse({ ok: false, error: 'No owner org found' });
                        return;
                    }

                    const hostname = message.hostname || new URL(message.url).hostname;
                    return fetch(`${base}/api/organizations/${ownerOrg.id}/credentials`, {
                        method: 'POST',
                        headers,
                        body: JSON.stringify({
                            service_type: 'other',
                            name: hostname,
                            website_url: message.url,
                            email: message.email,
                            password: message.password,
                        }),
                    });
                })
                .then(r => r?.json())
                .then(data => sendResponse({ ok: true, data }))
                .catch(err => sendResponse({ ok: false, error: err.message }));
        });
        return true; // keep async channel open
    }
});
