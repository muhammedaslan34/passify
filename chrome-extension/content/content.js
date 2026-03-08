(function () {
    'use strict';

    let saveBarShown = false;
    let fillBarShown = false;

    function mk(tag, css, attrs) {
        const e = document.createElement(tag);
        if (css) e.style.cssText = css;
        if (attrs) Object.assign(e, attrs);
        return e;
    }

    function injectStyles() {
        if (document.getElementById('passify-styles')) return;
        const s = document.createElement('style');
        s.id = 'passify-styles';
        s.textContent = [
            '@keyframes passify-slidein{from{opacity:0;transform:translateY(12px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}',
            '@keyframes passify-fadeout{from{opacity:1}to{opacity:0;transform:translateY(6px)}}',
        ].join('');
        document.head.appendChild(s);
    }

    function showToast(text, type) {
        injectStyles();
        const bg = type === 'success' ? '#16a34a' : '#dc2626';
        const icon = type === 'success' ? '✓' : '✕';
        const toast = mk('div', [
            'position:fixed', 'bottom:20px', 'right:20px', 'z-index:2147483648',
            `background:${bg}`, 'color:white', 'border-radius:10px',
            'padding:10px 16px', 'display:flex', 'align-items:center', 'gap:8px',
            'font-family:system-ui,-apple-system,sans-serif', 'font-size:13px', 'font-weight:500',
            'box-shadow:0 4px 16px rgba(0,0,0,.18)',
            'animation:passify-slidein .2s cubic-bezier(.16,1,.3,1)',
        ].join(';'));
        toast.appendChild(mk('span', 'font-size:15px;', { textContent: icon }));
        toast.appendChild(mk('span', '', { textContent: text }));
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'passify-fadeout .3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

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
        injectStyles();

        const cred = credentials[0];
        const hostname = window.location.hostname;
        const faviconUrl = `https://www.google.com/s2/favicons?domain=${hostname}&sz=32`;

        const card = mk('div', [
            'position:fixed', 'bottom:20px', 'right:20px', 'z-index:2147483647',
            'width:300px', 'background:#fff', 'border-radius:14px',
            'box-shadow:0 8px 32px rgba(0,0,0,.18),0 2px 8px rgba(0,0,0,.10)',
            'font-family:system-ui,-apple-system,sans-serif', 'overflow:hidden',
            'animation:passify-slidein .2s cubic-bezier(.16,1,.3,1)',
        ].join(';'));

        // header
        const hdr = mk('div', 'background:#4f46e5;padding:10px 14px;display:flex;align-items:center;gap:8px;');
        const hdrFav = mk('img', 'border-radius:4px;flex-shrink:0;width:16px;height:16px;');
        hdrFav.src = faviconUrl; hdrFav.onerror = () => hdrFav.style.display = 'none';
        hdr.appendChild(hdrFav);
        hdr.appendChild(mk('span', 'color:white;font-weight:600;font-size:12px;flex:1;', { textContent: 'Passify — Autofill' }));
        const closeBtn = mk('button', 'background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:17px;line-height:1;padding:0;', { textContent: '✕' });
        hdr.appendChild(closeBtn);
        card.appendChild(hdr);

        // credential row (avatar + name + email)
        const body = mk('div', 'padding:12px 14px 10px;display:flex;align-items:center;gap:10px;');
        const avatar = mk('div', 'width:36px;height:36px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px;');
        avatar.textContent = (cred.name || hostname).charAt(0).toUpperCase();
        const info = mk('div', 'flex:1;min-width:0;');
        info.appendChild(mk('div', 'font-weight:600;font-size:13px;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;', { textContent: cred.name || hostname }));
        info.appendChild(mk('div', 'font-size:11px;color:#6b7280;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;', { textContent: cred.email || 'No username' }));
        body.appendChild(avatar);
        body.appendChild(info);
        card.appendChild(body);

        // multiple credentials selector (if more than 1)
        let selectedCred = cred;
        if (credentials.length > 1) {
            const sel = mk('div', 'padding:0 14px 8px;');
            credentials.forEach((c, i) => {
                const opt = mk('div', `padding:6px 10px;border-radius:8px;cursor:pointer;font-size:12px;color:#374151;display:flex;align-items:center;gap:8px;${i === 0 ? 'background:#eef2ff;' : ''}`);
                const dot = mk('span', `width:8px;height:8px;border-radius:50%;background:${i === 0 ? '#4f46e5' : '#d1d5db'};flex-shrink:0;`);
                opt.appendChild(dot);
                opt.appendChild(mk('span', 'flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;', { textContent: `${c.name} — ${c.email || 'no email'}` }));
                opt.addEventListener('click', () => {
                    selectedCred = c;
                    sel.querySelectorAll('div').forEach((d, j) => {
                        d.style.background = j === i ? '#eef2ff' : '';
                        d.querySelector('span').style.background = j === i ? '#4f46e5' : '#d1d5db';
                    });
                });
                sel.appendChild(opt);
            });
            card.appendChild(sel);
        }

        // buttons
        const footer = mk('div', 'padding:0 14px 12px;display:flex;gap:8px;');
        const fillBtn   = mk('button', 'flex:1;background:#4f46e5;color:white;border:none;border-radius:8px;padding:8px 0;cursor:pointer;font-weight:600;font-size:13px;', { textContent: 'Autofill' });
        const notNowBtn = mk('button', 'flex:1;background:none;border:1px solid #e5e7eb;color:#6b7280;border-radius:8px;padding:8px 0;cursor:pointer;font-size:13px;', { textContent: 'Dismiss' });
        footer.appendChild(fillBtn);
        footer.appendChild(notNowBtn);
        card.appendChild(footer);

        document.body.appendChild(card);
        card.addEventListener('click', e => e.stopPropagation());

        fillBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fillCredential(selectedCred);
            card.remove();
        });
        closeBtn.addEventListener('click', (e) => { e.stopPropagation(); card.remove(); });
        notNowBtn.addEventListener('click', (e) => { e.stopPropagation(); card.remove(); });
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
            // Keep saveBarShown=true so the re-submit doesn't re-trigger the banner
            form.requestSubmit ? form.requestSubmit() : form.submit();
            setTimeout(() => { saveBarShown = false; }, 500);
        });
    }, true);

    function showSaveBanner(email, password, submitCallback) {
        saveBarShown = true;
        injectStyles();

        const hostname = window.location.hostname;
        const faviconUrl = `https://www.google.com/s2/favicons?domain=${hostname}&sz=32`;

        function label(text) {
            return mk('span', 'font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;', { textContent: text });
        }
        function field(content) {
            const d = mk('div', 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:7px 10px;display:flex;align-items:center;gap:6px;');
            d.appendChild(content);
            return d;
        }
        function row(labelText, fieldEl) {
            const d = mk('div', 'display:flex;flex-direction:column;gap:4px;');
            d.appendChild(label(labelText));
            d.appendChild(fieldEl);
            return d;
        }
        function favicon(size) {
            const img = mk('img', `border-radius:${size > 16 ? 4 : 3}px;flex-shrink:0;`);
            img.src = faviconUrl; img.width = size; img.height = size;
            img.onerror = () => img.style.display = 'none';
            return img;
        }

        // ── card ──────────────────────────────────────────────────────────
        const card = mk('div', [
            'position:fixed', 'bottom:20px', 'right:20px', 'z-index:2147483647',
            'width:320px', 'background:#ffffff', 'border-radius:14px',
            'box-shadow:0 8px 32px rgba(0,0,0,.18),0 2px 8px rgba(0,0,0,.10)',
            'font-family:system-ui,-apple-system,sans-serif', 'font-size:13px',
            'overflow:hidden', 'animation:passify-slidein .2s cubic-bezier(.16,1,.3,1)',
        ].join(';'));

        // header
        const header = mk('div', 'background:#4f46e5;padding:10px 14px;display:flex;align-items:center;gap:10px;');
        header.appendChild(favicon(18));
        header.appendChild(mk('span', 'color:white;font-weight:600;font-size:12px;flex:1;', { textContent: 'Save to Passify' }));
        const closeBtn = mk('button', 'background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:18px;line-height:1;padding:0;', { textContent: '✕' });
        header.appendChild(closeBtn);
        card.appendChild(header);

        // body
        const body = mk('div', 'padding:12px 14px;display:flex;flex-direction:column;gap:8px;');

        // website row
        const siteRow = mk('div', 'display:flex;align-items:center;gap:6px;');
        siteRow.appendChild(favicon(14));
        siteRow.appendChild(mk('span', 'color:#374151;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;', { textContent: hostname }));
        body.appendChild(row('Website', field(siteRow)));

        // username row
        body.appendChild(row('Username', field(
            mk('span', `color:${email ? '#374151' : '#9ca3af'};font-size:12px;flex:1;`, { textContent: email || 'No username detected' })
        )));

        // password row
        let pwdVisible = false;
        const pwdText = mk('span', 'color:#374151;font-size:13px;flex:1;letter-spacing:2px;', { textContent: '•'.repeat(Math.min(password.length, 20)) });
        const eyeBtn = mk('button', 'background:none;border:none;cursor:pointer;padding:0;color:#9ca3af;font-size:14px;line-height:1;flex-shrink:0;', { textContent: '👁' });
        eyeBtn.title = 'Show password';
        eyeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            pwdVisible = !pwdVisible;
            pwdText.style.letterSpacing = pwdVisible ? 'normal' : '2px';
            pwdText.textContent = pwdVisible ? password : '•'.repeat(Math.min(password.length, 20));
            eyeBtn.style.color = pwdVisible ? '#4f46e5' : '#9ca3af';
        });
        const pwdField = mk('div', 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:7px 10px;display:flex;align-items:center;gap:6px;');
        pwdField.appendChild(pwdText);
        pwdField.appendChild(eyeBtn);
        body.appendChild(row('Password', pwdField));
        card.appendChild(body);

        // footer buttons
        const footer = mk('div', 'padding:0 14px 14px;display:flex;gap:8px;');
        const saveBtn   = mk('button', 'flex:1;background:#4f46e5;color:white;border:none;border-radius:8px;padding:8px 0;cursor:pointer;font-weight:600;font-size:13px;', { textContent: 'Save' });
        const notNowBtn = mk('button', 'flex:1;background:none;border:1px solid #e5e7eb;color:#6b7280;border-radius:8px;padding:8px 0;cursor:pointer;font-size:13px;', { textContent: 'Not now' });
        footer.appendChild(saveBtn);
        footer.appendChild(notNowBtn);
        card.appendChild(footer);

        document.body.appendChild(card);

        // ── button handlers ───────────────────────────────────────────────
        card.addEventListener('click', (e) => e.stopPropagation());

        saveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            card.remove();
            chrome.runtime.sendMessage(
                { type: 'SAVE_CREDENTIAL', email, password, url: window.location.href, hostname },
                (res) => showToast(res?.ok ? 'Saved to Passify!' : 'Save failed — try again', res?.ok ? 'success' : 'error')
            );
            submitCallback(); // submitCallback resets saveBarShown after 500ms
        });

        const dismiss = (e) => {
            e.preventDefault();
            e.stopPropagation();
            card.remove();
            submitCallback(); // submitCallback resets saveBarShown after 500ms
        };
        closeBtn.addEventListener('click', dismiss);
        notNowBtn.addEventListener('click', dismiss);
    }

    // ── Extension token handshake ──────────────────────────────────────────
    function tryHandshake() {
        const meta = document.querySelector('meta[name="passify-extension-token"]');
        if (!meta) return;
        const token = meta.getAttribute('content');
        if (!token) return;

        chrome.runtime.sendMessage({ type: 'SAVE_TOKEN', token }, () => {
            document.getElementById('card-waiting').style.display = 'none';
            document.getElementById('card-success').style.display = 'block';
            setTimeout(() => chrome.runtime.sendMessage({ type: 'CLOSE_TAB' }), 1500);
        });
    }

    document.addEventListener('passify:token-ready', tryHandshake);
    tryHandshake(); // also try immediately in case event already fired

    // Delay autofill check to let the page settle
    setTimeout(checkAutofill, 1500);
})();
