const BASE = import.meta.env.VITE_API_URL || 'https://passify.pixeloud.com';

function getToken() {
    return localStorage.getItem('passify_token');
}

async function request(method, path, body = null) {
    const res = await fetch(`${BASE}/api${path}`, {
        method,
        headers: {
            'Authorization': `Bearer ${getToken()}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (res.status === 401) {
        localStorage.removeItem('passify_token');
        window.location.hash = '#/login';
        return null;
    }

    return res.json();
}

export const api = {
    login:            (email, password) => request('POST', '/auth/token', { email, password, device_name: 'pwa' }),
    logout:           ()                => request('DELETE', '/auth/token'),
    getOrgs:          ()                => request('GET', '/organizations'),
    getCredentials:   (orgId)           => request('GET', `/organizations/${orgId}/credentials`),
    createCredential: (orgId, data)     => request('POST', `/organizations/${orgId}/credentials`, data),
    updateCredential: (orgId, credId, data) => request('PUT', `/organizations/${orgId}/credentials/${credId}`, data),
    deleteCredential: (orgId, credId)   => request('DELETE', `/organizations/${orgId}/credentials/${credId}`),
    search:           (q, url = '')     => request('GET', `/credentials/search?q=${encodeURIComponent(q)}&url=${encodeURIComponent(url)}`),
    isLoggedIn:       ()                => !!getToken(),
    saveToken:        (token)           => localStorage.setItem('passify_token', token),
    clearToken:       ()                => localStorage.removeItem('passify_token'),
};
