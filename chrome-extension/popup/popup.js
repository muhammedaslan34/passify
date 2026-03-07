import { generate, strength } from '../shared/password-generator.js';

// ── Tab switching ─────────────────────────────────────────────────────────
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
    });
});

// ── Generator ─────────────────────────────────────────────────────────────
const slider = document.getElementById('length-slider');
const lengthDisplay = document.getElementById('length-display');
const passwordEl = document.getElementById('generated-password');
const strengthTextEl = document.getElementById('strength-text');
const strengthFill = document.getElementById('strength-fill');

function getOpts() {
    return {
        length: parseInt(slider.value, 10),
        lowercase: document.getElementById('opt-lowercase').checked,
        uppercase: document.getElementById('opt-uppercase').checked,
        numbers:   document.getElementById('opt-numbers').checked,
        symbols:   document.getElementById('opt-symbols').checked,
    };
}

function updateGenerator() {
    lengthDisplay.textContent = `${slider.value} characters`;
    const pwd = generate(getOpts());
    passwordEl.textContent = pwd;
    const s = strength(pwd);
    strengthTextEl.textContent = s.label;
    strengthTextEl.style.color = s.color;
    strengthFill.style.width = s.percent + '%';
    strengthFill.style.background = s.color;
}

slider.addEventListener('input', updateGenerator);
['opt-lowercase', 'opt-uppercase', 'opt-numbers', 'opt-symbols'].forEach(id => {
    document.getElementById(id).addEventListener('change', updateGenerator);
});
document.getElementById('generate-btn').addEventListener('click', updateGenerator);

document.getElementById('copy-btn').addEventListener('click', () => {
    navigator.clipboard.writeText(passwordEl.textContent);
    document.getElementById('copy-btn').textContent = '✓';
    setTimeout(() => { document.getElementById('copy-btn').textContent = '⧉'; }, 1500);
});

updateGenerator(); // generate on load

// ── Auth state ────────────────────────────────────────────────────────────
chrome.storage.local.get(['token'], ({ token }) => {
    document.getElementById('logged-out').style.display = token ? 'none' : 'block';
    document.getElementById('logged-in').style.display  = token ? 'block' : 'none';
});

document.getElementById('login-btn').addEventListener('click', () => {
    chrome.storage.local.get(['passifyUrl'], ({ passifyUrl }) => {
        const base = passifyUrl || 'http://localhost:8000';
        chrome.tabs.create({ url: `${base}/extension/auth` });
    });
});

document.getElementById('logout-btn').addEventListener('click', () => {
    chrome.storage.local.remove(['token'], () => {
        document.getElementById('logged-out').style.display = 'block';
        document.getElementById('logged-in').style.display  = 'none';
    });
});

// ── Search ────────────────────────────────────────────────────────────────
let searchTimeout;

document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const q = e.target.value.trim();
    const results = document.getElementById('search-results');

    if (!q) { results.innerHTML = ''; return; }

    searchTimeout = setTimeout(() => {
        chrome.runtime.sendMessage(
            { type: 'API_REQUEST', path: `/credentials/search?q=${encodeURIComponent(q)}`, method: 'GET' },
            (response) => {
                if (!response?.ok || !response.data?.data?.length) {
                    results.innerHTML = '<p class="no-results">No results found.</p>';
                    return;
                }
                results.innerHTML = response.data.data.map(c => `
                    <div class="result-item">
                        <div class="result-name">${escapeHtml(c.name)}</div>
                        <div class="result-org">${escapeHtml(c.organization_name)}</div>
                        <div class="result-actions">
                            <button class="copy-field" data-value="${escapeAttr(c.email || '')}">Copy email</button>
                            <button class="copy-field" data-value="${escapeAttr(c.password)}">Copy password</button>
                        </div>
                    </div>
                `).join('');

                results.querySelectorAll('.copy-field').forEach(btn => {
                    btn.addEventListener('click', () => {
                        navigator.clipboard.writeText(btn.dataset.value);
                        const original = btn.textContent;
                        btn.textContent = 'Copied!';
                        setTimeout(() => { btn.textContent = original; }, 1500);
                    });
                });
            }
        );
    }, 400);
});

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeAttr(str) {
    return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
