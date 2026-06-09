(function () {
    'use strict';

    const S = window.DogeSeedsAdmin?.strings || {};
    const apiBase = window.DogeSeedsAdmin?.apiBase || '../api/';

    const fieldMap = {
        site_name: 'siteName',
        site_url: 'siteUrl',
        default_language: 'defaultLanguage',
        map_default_lat: 'mapLat',
        map_default_lng: 'mapLng',
        map_default_zoom: 'mapZoom',
        doge_wallet: 'dogeWallet',
        doge_transparency_note: 'dogeNote',
        smtp_enabled: 'smtpEnabled',
        smtp_host: 'smtpHost',
        smtp_port: 'smtpPort',
        smtp_encryption: 'smtpEncryption',
        smtp_username: 'smtpUsername',
        smtp_from_email: 'smtpFromEmail',
        smtp_from_name: 'smtpFromName',
    };

    async function api(route, options = {}) {
        const res = await fetch(apiBase + route, {
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
            ...options,
        });
        const text = await res.text();
        let data = {};
        if (text) {
            try {
                data = JSON.parse(text);
            } catch {
                throw new Error(S.error || 'Server returned an invalid response. Check that API routes are configured correctly.');
            }
        }
        if (!res.ok) throw new Error(data.error || 'Request failed');
        return data;
    }

    function showStatus(msg, ok = true) {
        const el = document.getElementById('adminStatus');
        if (!el) return;
        el.hidden = false;
        el.textContent = msg;
        el.classList.toggle('is-error', !ok);
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function collectPayload() {
        const form = document.getElementById('adminForm');
        if (!form) return {};

        const fd = new FormData(form);
        const payload = {
            site_name: fd.get('site_name'),
            site_url: fd.get('site_url'),
            default_language: fd.get('default_language'),
            map_default_lat: fd.get('map_default_lat'),
            map_default_lng: fd.get('map_default_lng'),
            map_default_zoom: fd.get('map_default_zoom'),
            doge_wallet: fd.get('doge_wallet'),
            doge_transparency_note: fd.get('doge_transparency_note'),
            smtp_enabled: fd.get('smtp_enabled') ? 1 : 0,
            smtp_host: fd.get('smtp_host'),
            smtp_port: fd.get('smtp_port'),
            smtp_encryption: fd.get('smtp_encryption'),
            smtp_username: fd.get('smtp_username'),
            smtp_from_email: fd.get('smtp_from_email'),
            smtp_from_name: fd.get('smtp_from_name'),
        };

        const password = fd.get('smtp_password');
        if (password) {
            payload.smtp_password = password;
        }

        return payload;
    }

    async function loadSettings() {
        const data = await api('admin/settings');
        const s = data.settings || {};

        Object.entries(fieldMap).forEach(([key, id]) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.type === 'checkbox') {
                el.checked = !!s[key];
            } else if (s[key] !== undefined && s[key] !== null) {
                el.value = s[key];
            }
        });
    }

    document.getElementById('adminForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await api('admin/settings', {
                method: 'POST',
                body: JSON.stringify(collectPayload()),
            });
            document.getElementById('smtpPassword').value = '';
            showStatus(S.saved || 'Settings saved');
        } catch (err) {
            showStatus(err.message, false);
        }
    });

    document.getElementById('btnTestEmail')?.addEventListener('click', async () => {
        try {
            await api('admin/settings', {
                method: 'POST',
                body: JSON.stringify(collectPayload()),
            });
            await api('admin/test_email', { method: 'POST', body: '{}' });
            document.getElementById('smtpPassword').value = '';
            showStatus(S.test_sent || 'Test email sent');
        } catch (err) {
            showStatus(err.message, false);
        }
    });

    loadSettings().catch((err) => showStatus(err.message, false));
})();
