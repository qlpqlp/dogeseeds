(function () {
    'use strict';

    const S = window.DogeSeedsAdmin?.strings || {};
    const apiBase = window.DogeSeedsAdmin?.apiBase || '../api/';
    const adminId = window.DogeSeedsAdmin?.adminId || 0;
    const mapDefaults = window.DogeSeedsAdmin?.mapDefaults || { lat: 38.7223, lng: -9.1393, zoom: 6 };
    const ADMIN_PIN_COLOR = '#4CAF50';

    let adminPickMap = null;
    let adminPickMarker = null;

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

    let adminListings = [];
    let adminUsers = [];
    let listingsSearchTimer = null;
    let usersSearchTimer = null;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    async function api(route, options = {}) {
        const base = apiBase.endsWith('/') ? apiBase : apiBase + '/';
        const isFormData = options.body instanceof FormData;
        const fetchOpts = {
            credentials: 'same-origin',
            ...options,
        };
        if (!isFormData) {
            fetchOpts.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
        }
        const res = await fetch(base + route.replace(/^\//, ''), fetchOpts);
        const text = await res.text();
        let data = {};
        if (text) {
            try {
                data = JSON.parse(text);
            } catch {
                throw new Error(S.error || 'Server returned an invalid response.');
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

    function onAdminTab(tab) {
        if (tab === 'listings') loadAdminListings();
        if (tab === 'users') loadAdminUsers();
    }

    function toDatetimeLocalFromIso(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '';
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function formatDate(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return iso;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    async function loadAdminListings(q = '') {
        const container = document.getElementById('adminListingsList');
        if (!container) return;
        container.innerHTML = '<p class="hint">…</p>';

        try {
            const route = q ? `my/listings?q=${encodeURIComponent(q)}` : 'my/listings';
            const data = await api(route);
            adminListings = data.listings || [];
            renderAdminListings();
        } catch (err) {
            container.innerHTML = `<p class="admin-status is-error">${esc(err.message)}</p>`;
        }
    }

    function renderAdminListings() {
        const container = document.getElementById('adminListingsList');
        if (!container) return;

        if (!adminListings.length) {
            container.innerHTML = `<p class="hint">${esc(S.listings_empty || 'No listings found')}</p>`;
            return;
        }

        container.innerHTML = `
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>${esc(S.edit || 'Edit')}</th>
                        <th>Status</th>
                        <th>Name</th>
                        <th>Owner</th>
                        <th>City</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${adminListings.map(listing => {
                        const inactive = !Number(listing.active);
                        const owner = listing.owner_name
                            ? `${listing.owner_name} (${listing.owner_email || ''})`
                            : (listing.owner_email || '—');
                        const statusLabel = inactive ? (S.listing_hidden || 'Hidden') : (S.listing_live || 'Live');
                        return `
                        <tr class="${inactive ? 'is-inactive' : ''}">
                            <td><button type="button" class="btn btn-sm btn-outline btn-icon admin-listing-edit" data-id="${listing.location_id}" title="${esc(S.edit || 'Edit')}" aria-label="${esc(S.edit || 'Edit')}"><span class="material-icons">edit</span></button></td>
                            <td><span class="admin-badge ${inactive ? 'inactive' : 'active'}">${esc(statusLabel)}</span></td>
                            <td><strong>${esc(listing.org_name || listing.location_name)}</strong></td>
                            <td>${esc(owner)}</td>
                            <td>${esc(listing.city || '—')}</td>
                            <td class="admin-row-actions">
                                ${inactive
                                    ? `<button type="button" class="btn btn-sm btn-outline btn-icon admin-listing-show" data-id="${listing.location_id}" title="${esc(S.show || 'Show')}" aria-label="${esc(S.show || 'Show')}"><span class="material-icons">visibility</span></button>`
                                    : `<button type="button" class="btn btn-sm btn-outline btn-icon admin-listing-hide" data-id="${listing.location_id}" title="${esc(S.hide || 'Hide')}" aria-label="${esc(S.hide || 'Hide')}"><span class="material-icons">visibility_off</span></button>`}
                                <button type="button" class="btn btn-sm btn-outline btn-icon admin-listing-remove" data-id="${listing.location_id}" title="${esc(S.remove || 'Remove')}" aria-label="${esc(S.remove || 'Remove')}"><span class="material-icons">delete</span></button>
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>`;

        container.querySelectorAll('.admin-listing-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const listing = adminListings.find(l => l.location_id == btn.dataset.id);
                if (listing) showAdminListingEdit(listing);
            });
        });

        container.querySelectorAll('.admin-listing-hide').forEach(btn => {
            btn.addEventListener('click', () => hideAdminListing(Number(btn.dataset.id)));
        });

        container.querySelectorAll('.admin-listing-show').forEach(btn => {
            btn.addEventListener('click', () => showAdminListing(Number(btn.dataset.id)));
        });

        container.querySelectorAll('.admin-listing-remove').forEach(btn => {
            btn.addEventListener('click', () => removeAdminListing(Number(btn.dataset.id)));
        });
    }

    function setAdminListingPhotoPreview(url) {
        const preview = document.getElementById('adminEditImagePreview');
        const previewImg = document.getElementById('adminEditImagePreviewImg');
        const uploadBtn = document.getElementById('adminEditImageUploadBtn');
        const uploadLabel = document.getElementById('adminEditImageUploadLabel');
        const fileInput = document.getElementById('adminEditImage');
        const removeFlag = document.getElementById('adminEditRemoveImage');

        if (!preview || !previewImg) return;

        if (url) {
            previewImg.src = url;
            preview.classList.remove('hidden');
            uploadBtn?.classList.remove('hidden');
            if (uploadLabel) uploadLabel.textContent = S.photo_replace || 'Replace photo';
            if (removeFlag) removeFlag.value = '0';
        } else {
            previewImg.removeAttribute('src');
            preview.classList.add('hidden');
            uploadBtn?.classList.remove('hidden');
            if (uploadLabel) uploadLabel.textContent = S.photo_choose || 'Choose photo';
            if (removeFlag) removeFlag.value = '0';
        }

        if (fileInput) fileInput.value = '';
    }

    function clearAdminListingPhoto() {
        const removeFlag = document.getElementById('adminEditRemoveImage');
        if (removeFlag) removeFlag.value = '1';
        setAdminListingPhotoPreview(null);
    }

    function initAdminListingPhoto() {
        const input = document.getElementById('adminEditImage');
        const removeBtn = document.getElementById('adminEditImageRemove');
        if (!input || input.dataset.bound) return;
        input.dataset.bound = '1';

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showStatus(S.photo_too_large || 'Max 5 MB', false);
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                const removeFlag = document.getElementById('adminEditRemoveImage');
                if (removeFlag) removeFlag.value = '0';
                setAdminListingPhotoPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        });

        removeBtn?.addEventListener('click', () => clearAdminListingPhoto());
    }

    function adminMaterialPin(color) {
        return L.divIcon({
            className: 'custom-pin',
            html: `<span class="map-pin-badge" style="width:40px;height:40px;border-color:${color};color:${color};"><span class="material-icons" style="font-size:24px;">location_on</span></span>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
        });
    }

    function setAdminPickLocation(latlng, addressText) {
        if (!adminPickMap || typeof L === 'undefined') return;
        if (adminPickMarker) adminPickMap.removeLayer(adminPickMarker);
        adminPickMarker = L.marker(latlng, { icon: adminMaterialPin(ADMIN_PIN_COLOR), draggable: true })
            .addTo(adminPickMap);
        adminPickMarker.on('dragend', () => {
            const p = adminPickMarker.getLatLng();
            document.getElementById('adminPickLat').value = p.lat.toFixed(8);
            document.getElementById('adminPickLng').value = p.lng.toFixed(8);
        });
        document.getElementById('adminPickLat').value = latlng.lat.toFixed(8);
        document.getElementById('adminPickLng').value = latlng.lng.toFixed(8);
        adminPickMap.setView(latlng, Math.max(adminPickMap.getZoom(), 14));
        if (addressText && document.getElementById('adminEditAddress')) {
            document.getElementById('adminEditAddress').value = addressText;
        }
    }

    function initAdminPickMap(listing) {
        const el = document.getElementById('adminPickMap');
        if (!el || typeof L === 'undefined') return;

        const lat = parseFloat(listing?.latitude);
        const lng = parseFloat(listing?.longitude);
        const hasCoords = Number.isFinite(lat) && Number.isFinite(lng);
        const start = hasCoords ? [lat, lng] : [mapDefaults.lat, mapDefaults.lng];
        const zoom = hasCoords ? 14 : mapDefaults.zoom;

        if (!adminPickMap) {
            adminPickMap = L.map('adminPickMap').setView(start, zoom);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OSM',
                maxZoom: 19,
            }).addTo(adminPickMap);

            if (typeof L.Control.Geocoder !== 'undefined') {
                const geocoder = L.Control.geocoder({
                    geocoder: L.Control.Geocoder.nominatim(),
                    position: 'topleft',
                    defaultMarkGeocode: false,
                    collapsed: false,
                    placeholder: S.search_address || 'Search address…',
                }).addTo(adminPickMap);
                geocoder.on('markgeocode', (e) => {
                    setAdminPickLocation(e.geocode.center, e.geocode.name || '');
                });
            }

            adminPickMap.on('click', (e) => setAdminPickLocation(e.latlng));

            document.getElementById('adminBtnPickMyLocation')?.addEventListener('click', () => {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition((pos) => {
                    setAdminPickLocation(L.latLng(pos.coords.latitude, pos.coords.longitude));
                });
            });
        } else {
            adminPickMap.setView(start, zoom);
            adminPickMap.invalidateSize();
        }

        if (hasCoords) {
            setAdminPickLocation(L.latLng(lat, lng));
        } else {
            if (adminPickMarker) {
                adminPickMap.removeLayer(adminPickMarker);
                adminPickMarker = null;
            }
            document.getElementById('adminPickLat').value = '';
            document.getElementById('adminPickLng').value = '';
        }

        setTimeout(() => adminPickMap?.invalidateSize(), 250);
    }

    function showAdminListingEdit(listing) {
        document.getElementById('adminListingEditCard')?.classList.remove('hidden');
        document.getElementById('adminEditLocationId').value = listing.location_id;
        document.getElementById('adminEditOrgName').value = listing.org_name || '';

        const orgType = listing.org_type || 'donor';
        document.querySelectorAll('#adminListingEditForm input[name="type"]').forEach((radio) => {
            radio.checked = radio.value === orgType;
        });

        document.querySelectorAll('#adminListingEditForm input[name="offers[]"]').forEach((cb) => {
            cb.checked = (listing.offers || []).includes(cb.value);
        });
        document.querySelectorAll('#adminListingEditForm input[name="needs[]"]').forEach((cb) => {
            cb.checked = (listing.needs || []).includes(cb.value);
        });

        document.getElementById('adminEditDescription').value = listing.org_description || '';
        document.getElementById('adminEditInstructions').value = listing.instructions || '';
        document.getElementById('adminEditAddress').value = listing.address || '';
        document.getElementById('adminEditCity').value = listing.city || '';
        document.getElementById('adminEditCountry').value = listing.country_code || '';
        document.getElementById('adminEditContactEmail').value = listing.contact_email || '';
        document.getElementById('adminEditContactPhone').value = listing.contact_phone || '';
        document.getElementById('adminEditShowContact').checked = !!Number(listing.show_contact_public);
        setAdminListingPhotoPreview(listing.image_url || null);

        const donations = (listing.donations || []).filter(d => ['available', 'reserved'].includes(d.status));
        const ref = donations[0];
        document.getElementById('adminEditPickupStart').value = toDatetimeLocalFromIso(ref?.pickup_start);
        document.getElementById('adminEditPickupEnd').value = toDatetimeLocalFromIso(ref?.pickup_end);

        const ownerInfo = document.getElementById('adminEditOwnerInfo');
        if (ownerInfo) {
            ownerInfo.textContent = `${S.owner || 'Owner'}: ${listing.owner_name || ''} (${listing.owner_email || ''})`;
        }

        setTimeout(() => initAdminPickMap(listing), 200);
        document.getElementById('adminListingEditCard')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function setListingActive(locationId, active) {
        await api('my/listings', {
            method: 'PUT',
            body: JSON.stringify({ location_id: locationId, active: active ? 1 : 0 }),
        });
    }

    async function hideAdminListing(locationId) {
        if (!confirm(S.hide_confirm || 'Hide this listing from the map?')) return;
        try {
            await setListingActive(locationId, false);
            showStatus(S.saved || 'Saved');
            await loadAdminListings(document.getElementById('adminListingsSearch')?.value || '');
        } catch (err) {
            showStatus(err.message, false);
        }
    }

    async function showAdminListing(locationId) {
        try {
            await setListingActive(locationId, true);
            showStatus(S.saved || 'Saved');
            await loadAdminListings(document.getElementById('adminListingsSearch')?.value || '');
        } catch (err) {
            showStatus(err.message, false);
        }
    }

    async function removeAdminListing(locationId) {
        if (!confirm(S.remove_confirm || 'Remove this listing from the map?')) return;
        try {
            await api('my/listings', {
                method: 'DELETE',
                body: JSON.stringify({ location_id: locationId }),
            });
            showStatus(S.saved || 'Saved');
            document.getElementById('adminListingEditCard')?.classList.add('hidden');
            await loadAdminListings(document.getElementById('adminListingsSearch')?.value || '');
        } catch (err) {
            showStatus(err.message, false);
        }
    }

    async function loadAdminUsers(q = '') {
        const container = document.getElementById('adminUsersList');
        if (!container) return;
        container.innerHTML = '<p class="hint">…</p>';

        try {
            const route = q ? `admin/users?q=${encodeURIComponent(q)}` : 'admin/users';
            const data = await api(route);
            adminUsers = data.users || [];
            renderAdminUsers();
        } catch (err) {
            container.innerHTML = `<p class="admin-status is-error">${esc(err.message)}</p>`;
        }
    }

    function renderAdminUsers() {
        const container = document.getElementById('adminUsersList');
        if (!container) return;

        if (!adminUsers.length) {
            container.innerHTML = `<p class="hint">${esc(S.users_empty || 'No users found')}</p>`;
            return;
        }

        container.innerHTML = `
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>${esc(S.edit || 'Edit')}</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Listings</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${adminUsers.map(u => {
                        const blocked = !!Number(u.blocked);
                        const verified = !!Number(u.verified);
                        let statusLabel = 'Pending';
                        let statusClass = 'inactive';
                        if (blocked) {
                            statusLabel = S.block || 'Blocked';
                            statusClass = 'inactive';
                        } else if (verified) {
                            statusLabel = 'Verified';
                            statusClass = 'active';
                        }
                        const isSelf = Number(u.id) === adminId;
                        return `
                        <tr class="${blocked ? 'is-inactive' : ''}">
                            <td><button type="button" class="btn btn-sm btn-outline btn-icon admin-user-edit" data-id="${u.id}" title="${esc(S.edit || 'Edit')}" aria-label="${esc(S.edit || 'Edit')}"><span class="material-icons">edit</span></button></td>
                            <td><strong>${esc(u.name)}</strong></td>
                            <td>${esc(u.email)}</td>
                            <td><span class="admin-badge inactive">${esc(u.role)}</span></td>
                            <td><span class="admin-badge ${statusClass}">${esc(statusLabel)}</span></td>
                            <td>${esc(String(u.listing_count || 0))}</td>
                            <td class="admin-row-actions">
                                ${!isSelf && !blocked ? `<button type="button" class="btn btn-sm btn-outline btn-icon admin-user-block" data-id="${u.id}" title="${esc(S.block || 'Block')}" aria-label="${esc(S.block || 'Block')}"><span class="material-icons">block</span></button>` : ''}
                                ${!isSelf && blocked ? `<button type="button" class="btn btn-sm btn-outline btn-icon admin-user-unblock" data-id="${u.id}" title="${esc(S.unblock || 'Unblock')}" aria-label="${esc(S.unblock || 'Unblock')}"><span class="material-icons">lock_open</span></button>` : ''}
                                ${!isSelf ? `<button type="button" class="btn btn-sm btn-outline btn-icon admin-user-delete" data-id="${u.id}" title="${esc(S.delete_user || 'Delete')}" aria-label="${esc(S.delete_user || 'Delete')}"><span class="material-icons">delete</span></button>` : ''}
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>`;

        container.querySelectorAll('.admin-user-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const user = adminUsers.find(u => u.id == btn.dataset.id);
                if (user) showAdminUserEdit(user);
            });
        });

        container.querySelectorAll('.admin-user-block').forEach(btn => {
            btn.addEventListener('click', () => toggleUserBlocked(Number(btn.dataset.id), true));
        });

        container.querySelectorAll('.admin-user-unblock').forEach(btn => {
            btn.addEventListener('click', () => toggleUserBlocked(Number(btn.dataset.id), false));
        });

        container.querySelectorAll('.admin-user-delete').forEach(btn => {
            btn.addEventListener('click', () => deleteAdminUser(Number(btn.dataset.id)));
        });
    }

    function showAdminUserEdit(user) {
        document.getElementById('adminUserEditCard')?.classList.remove('hidden');
        document.getElementById('adminEditUserId').value = user.id;
        document.getElementById('adminEditUserName').value = user.name || '';
        document.getElementById('adminEditUserEmail').value = user.email || '';
        document.getElementById('adminEditUserRole').value = user.role || 'user';
        document.getElementById('adminEditUserVerified').checked = !!Number(user.verified);
        document.getElementById('adminEditUserBlocked').checked = !!Number(user.blocked);
        document.getElementById('adminEditUserBlocked').disabled = Number(user.id) === adminId;

        const meta = document.getElementById('adminEditUserMeta');
        if (meta) {
            meta.textContent = `${S.registered || 'Registered'}: ${formatDate(user.created_at)} · ${S.listings_count || 'Listings'}: ${user.listing_count || 0}`;
        }

        document.getElementById('adminUserEditCard')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function toggleUserBlocked(userId, blocked) {
        try {
            await api('admin/users', {
                method: 'PUT',
                body: JSON.stringify({ id: userId, blocked: blocked ? 1 : 0 }),
            });
            showStatus(S.saved || 'Saved');
            await loadAdminUsers(document.getElementById('adminUsersSearch')?.value || '');
        } catch (err) {
            showStatus(err.message, false);
        }
    }

    async function deleteAdminUser(userId) {
        if (!confirm(S.delete_user_confirm || 'Delete this user and all their listings?')) return;
        try {
            await api('admin/users', {
                method: 'DELETE',
                body: JSON.stringify({ id: userId }),
            });
            showStatus(S.saved || 'Saved');
            document.getElementById('adminUserEditCard')?.classList.add('hidden');
            await loadAdminUsers(document.getElementById('adminUsersSearch')?.value || '');
        } catch (err) {
            showStatus(err.message, false);
        }
    }

    document.addEventListener('dogeseeds-admin-tab', (e) => {
        onAdminTab(e.detail?.tab);
    });

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

    document.getElementById('adminListingsSearch')?.addEventListener('input', (e) => {
        clearTimeout(listingsSearchTimer);
        listingsSearchTimer = setTimeout(() => loadAdminListings(e.target.value), 300);
    });

    document.getElementById('adminUsersSearch')?.addEventListener('input', (e) => {
        clearTimeout(usersSearchTimer);
        usersSearchTimer = setTimeout(() => loadAdminUsers(e.target.value), 300);
    });

    document.getElementById('adminCancelListingEdit')?.addEventListener('click', () => {
        document.getElementById('adminListingEditCard')?.classList.add('hidden');
    });

    document.getElementById('adminCancelUserEdit')?.addEventListener('click', () => {
        document.getElementById('adminUserEditCard')?.classList.add('hidden');
    });

    document.getElementById('adminListingEditForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const pickupStart = fd.get('pickup_start');
        const pickupEnd = fd.get('pickup_end');
        if (pickupStart && pickupEnd && new Date(String(pickupEnd)) <= new Date(String(pickupStart))) {
            showStatus('Invalid pickup window', false);
            return;
        }
        try {
            if (pickupStart && pickupEnd) {
                fd.set('pickup_start', String(pickupStart));
                fd.set('pickup_end', String(pickupEnd));
            }
            fd.set('show_contact_public', document.getElementById('adminEditShowContact')?.checked ? '1' : '0');
            await api('my/listings', { method: 'PUT', body: fd });
            showStatus(S.saved || 'Saved');
            document.getElementById('adminListingEditCard')?.classList.add('hidden');
            await loadAdminListings(document.getElementById('adminListingsSearch')?.value || '');
        } catch (err) {
            showStatus(err.message, false);
        }
    });

    document.getElementById('adminUserEditForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await api('admin/users', {
                method: 'PUT',
                body: JSON.stringify({
                    id: Number(fd.get('id')),
                    name: fd.get('name'),
                    email: fd.get('email'),
                    role: fd.get('role'),
                    verified: fd.get('verified') ? 1 : 0,
                    blocked: fd.get('blocked') ? 1 : 0,
                }),
            });
            showStatus(S.saved || 'Saved');
            document.getElementById('adminUserEditCard')?.classList.add('hidden');
            await loadAdminUsers(document.getElementById('adminUsersSearch')?.value || '');
        } catch (err) {
            showStatus(err.message, false);
        }
    });

    initAdminListingPhoto();
    loadSettings().catch((err) => showStatus(err.message, false));
})();
