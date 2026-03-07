(function () {
    'use strict';

    let saveBarShown = false;
    let fillBarShown = false;

    // ── Autofill: check if current URL has saved credentials ──────────────
    function checkAutofill() {
        const hostname = window.location.hostname;
        chrome.runtime.sendMessage(
            { type: 'API_REQUEST', path: `/credentials/search?url=${encodeURIComponent(hostname)}`, method: 'GET' },
            (response) => {
                if (response?.ok && response.data?.data?.length > 0 && !fillBarShown) {
                    showAutofillBanner(response.data.data);
                }
            }
        );
    }

    function showAutofillBanner(credentials) {
        fillBarShown = true;
        const bar = document.createElement('div');
        bar.id = 'passify-autofill-bar';
        bar.style.cssText = [
            'position:fixed', 'top:0', 'left:0', 'right:0', 'z-index:2147483647',
            'background:#4f46e5', 'color:white', 'padding:10px 16px',
            'display:flex', 'align-items:center', 'gap:12px',
            'font-family:system-ui,sans-serif', 'font-size:13px',
            'box-shadow:0 2px 8px rgba(0,0,0,.2)'
        ].join(';');

        const cred = credentials[0];
        const nameEl = document.createElement('span');
        nameEl.style.cssText = 'flex:1';
        nameEl.textContent = `Passify: Fill "${cred.name}" (${cred.email || 'no email'})?`;

        const fillBtn = document.createElement('button');
        fillBtn.id = 'passify-fill-btn';
        fillBtn.textContent = 'Autofill';
        fillBtn.style.cssText = 'background:white;color:#4f46e5;border:none;border-radius:6px;padding:4px 12px;cursor:pointer;font-weight:600;font-size:12px';

        const dismissBtn = document.createElement('button');
        dismissBtn.id = 'passify-fill-dismiss';
        dismissBtn.textContent = '✕';
        dismissBtn.style.cssText = 'background:transparent;border:none;color:white;cursor:pointer;font-size:16px;line-height:1';

        bar.appendChild(nameEl);
        bar.appendChild(fillBtn);
        bar.appendChild(dismissBtn);
        document.body.prepend(bar);

        fillBtn.addEventListener('click', () => {
            fillCredential(cred);
            bar.remove();
        });
        dismissBtn.addEventListener('click', () => bar.remove());
    }

    function fillCredential(cred) {
        const emailInput = document.querySelector(
            'input[type="email"], input[name*="email"], input[name*="user"], input[name*="login"]'
        );
        const passwordInput = document.querySelector('input[type="password"]');

        if (emailInput && cred.email) {
            emailInput.value = cred.email;
            emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (passwordInput) {
            passwordInput.value = cred.password;
            passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    // ── Save: intercept form submit with password field ────────────────────
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        const passwordInput = form.querySelector('input[type="password"]');
        if (!passwordInput || saveBarShown) return;

        const password = passwordInput.value;
        if (!password) return;

        const emailInput = form.querySelector('input[type="email"], input[name*="email"], input[name*="user"]');
        const email = emailInput?.value || '';

        e.preventDefault();
        showSaveBanner(email, password, () => {
            saveBarShown = false;
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    }, true);

    function showSaveBanner(email, password, submitCallback) {
        saveBarShown = true;
        const bar = document.createElement('div');
        bar.id = 'passify-save-bar';
        bar.style.cssText = [
            'position:fixed', 'bottom:0', 'left:0', 'right:0', 'z-index:2147483647',
            'background:#1e1b4b', 'color:white', 'padding:12px 16px',
            'display:flex', 'align-items:center', 'gap:12px',
            'font-family:system-ui,sans-serif', 'font-size:13px',
            'box-shadow:0 -2px 8px rgba(0,0,0,.2)'
        ].join(';');

        const msgEl = document.createElement('span');
        msgEl.style.flex = '1';
        msgEl.textContent = `Save "${window.location.hostname}" credentials to Passify?`;

        const saveBtn = document.createElement('button');
        saveBtn.textContent = 'Save';
        saveBtn.style.cssText = 'background:#4f46e5;color:white;border:none;border-radius:6px;padding:4px 12px;cursor:pointer;font-weight:600;font-size:12px';

        const dismissBtn = document.createElement('button');
        dismissBtn.textContent = 'Not now';
        dismissBtn.style.cssText = 'background:transparent;border:none;color:#a5b4fc;cursor:pointer;font-size:12px';

        bar.appendChild(msgEl);
        bar.appendChild(saveBtn);
        bar.appendChild(dismissBtn);
        document.body.appendChild(bar);

        saveBtn.addEventListener('click', () => {
            bar.remove();
            chrome.runtime.sendMessage({
                type: 'SAVE_CREDENTIAL',
                email,
                password,
                url: window.location.href,
                hostname: window.location.hostname,
            });
            submitCallback();
        });

        dismissBtn.addEventListener('click', () => {
            bar.remove();
            submitCallback();
        });
    }

    // Delay autofill check to let the page settle
    setTimeout(checkAutofill, 1500);
})();
