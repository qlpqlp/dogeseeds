/**
 * DogeSeeds.org — Map-first application
 */
(function () {
    'use strict';

    const DS = window.DogeSeeds;
    const S = DS.strings;
    let map = null;
    let markers = [];
    const markerByLocationId = new Map();
    let highlightedMarker = null;
    let pickMap = null;
    let pickMarker = null;
    let pickGeocoder = null;
    let currentCategory = '';
    let listSearchQuery = '';
    let listSearchTimer = null;
    let userLocation = null;
    let locations = [];
    let activePanel = null;
    let resetSlideSubmit = null;
    let resetInquirySlide = null;
    let splashActive = true;
    let splashExitPromise = null;
    let splashLogoInterval = null;

    const panelMap = {
        list: 'listPanel',
        donate: 'donatePanel',
        post: 'postPanel',
        detail: 'detailPanel',
        auth: 'authPanel',
        my: 'myPanel',
    };

    let myListings = [];
    let editingListingId = null;

    const PICK_PIN_COLOR = '#4CAF50';

    let modalResolve = null;

    // ── Toasts & modals ──
    function showToast(message, type = 'info') {
        const stack = document.getElementById('toastStack');
        if (!stack) return;
        const icons = { success: 'check_circle', error: 'error', info: 'info' };
        const toast = document.createElement('div');
        toast.className = `ds-toast ds-toast-${type}`;
        toast.innerHTML = `<span class="material-icons">${icons[type] || 'info'}</span><span>${esc(String(message))}</span>`;
        stack.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 320);
        }, 3800);
    }

    function closeModal(result) {
        const backdrop = document.getElementById('dsModalBackdrop');
        backdrop?.classList.remove('open');
        backdrop?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ds-modal-open');
        if (modalResolve) {
            const r = modalResolve;
            modalResolve = null;
            r(result);
        }
    }

    function openModal({ title, message = '', icon = 'info', confirmLabel, cancelLabel, showCancel = true, variant = 'info' }) {
        return new Promise((resolve) => {
            modalResolve = resolve;
            const backdrop = document.getElementById('dsModalBackdrop');
            const iconEl = document.getElementById('dsModalIcon');
            const titleEl = document.getElementById('dsModalTitle');
            const msgEl = document.getElementById('dsModalMessage');
            const cancelBtn = document.getElementById('dsModalCancel');
            const confirmBtn = document.getElementById('dsModalConfirm');
            if (!backdrop) {
                resolve(false);
                return;
            }

            iconEl.innerHTML = `<span class="material-icons">${icon}</span>`;
            iconEl.className = `ds-modal-icon ds-modal-${variant}`;
            titleEl.textContent = title || '';
            msgEl.textContent = message || '';
            titleEl.classList.toggle('hidden', !title);
            msgEl.classList.toggle('hidden', !message);
            confirmBtn.textContent = confirmLabel || S.confirm || 'OK';
            cancelBtn.textContent = cancelLabel || S.cancel || 'Cancel';
            cancelBtn.classList.toggle('hidden', !showCancel);
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ds-modal-open');
            confirmBtn.focus();
        });
    }

    function showAlert(message, { title = '', icon = 'info', variant = 'info' } = {}) {
        return openModal({
            title: title || message,
            message: title ? message : '',
            icon,
            showCancel: false,
            variant,
            confirmLabel: S.confirm || 'OK',
        });
    }

    function showConfirm(title, { desc = '', icon = 'help_outline' } = {}) {
        return openModal({ title, message: desc, icon, showCancel: true, variant: 'confirm' });
    }

    function toDatetimeLocalValue(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function setDefaultPickupTimes() {
        const startEl = document.getElementById('pickupStart');
        const endEl = document.getElementById('pickupEnd');
        if (!startEl || !endEl || (startEl.value && endEl.value)) return;
        const start = new Date();
        start.setMinutes(Math.ceil(start.getMinutes() / 15) * 15, 0, 0);
        const end = new Date(start);
        end.setDate(end.getDate() + 7);
        startEl.value = toDatetimeLocalValue(start);
        endEl.value = toDatetimeLocalValue(end);
    }

    function pickupWindowLabel(loc) {
        const donations = loc.donations || [];
        if (!donations.length) return '';
        const ref = donations.find(d => d.pickup_start && d.pickup_end && d.status === 'available')
            || donations.find(d => d.pickup_start && d.pickup_end)
            || donations[0];
        const start = ref.pickup_start;
        const end = ref.pickup_end;
        if (!start || !end) return '';
        return `${formatDate(start)} – ${formatDate(end)}`;
    }

    function popupBadgeRow(items, label) {
        if (!items?.length) return '';
        const badges = items.map(c =>
            `<span class="badge badge-${c}">${esc(S['filter_' + c] || c)}</span>`
        ).join('');
        return `<div class="map-popup-row">
            <span class="map-popup-row-label">${esc(label)}</span>
            <div class="map-popup-badges">${badges}</div>
        </div>`;
    }

    function buildMapPopup(loc) {
        const orgName = loc.org_name || '';
        const locName = loc.location_name && loc.location_name !== loc.org_name ? loc.location_name : '';
        const typeLabel = S['org_' + loc.org_type] || loc.org_type;
        const typeIcon = loc.icon || 'place';
        const color = loc.marker_color || '#4CAF50';

        const verified = loc.org_verified == 1
            ? `<span class="map-popup-verified"><span class="material-icons">verified</span>${esc(S.verified)}</span>`
            : '';

        const offersRow = popupBadgeRow(loc.offers, S.we_offer);
        const needsRow = popupBadgeRow(loc.needs, S.we_need);

        const donations = (loc.donations || []).filter(d => d.status === 'available');
        const itemsHtml = donations.length
            ? `<ul class="map-popup-items">${donations.map(d => `
                <li>
                    <span class="badge badge-${d.category}">${esc(S['filter_' + d.category] || d.category)}</span>
                    <span class="map-popup-item-title">${esc(d.title)}</span>
                </li>`).join('')}</ul>`
            : '';

        const pickup = pickupWindowLabel(loc);
        const pickupHtml = pickup
            ? `<div class="map-popup-pickup">
                <span class="material-icons">schedule</span>
                <div>
                    <strong>${esc(S.pickup_window)}</strong>
                    <span>${esc(pickup)}</span>
                </div>
            </div>`
            : '';

        const address = formatLocationAddress(loc);
        const addressHtml = address
            ? `<div class="map-popup-address"><span class="material-icons">place</span><span>${esc(address)}</span></div>`
            : '';

        const hasContent = offersRow || needsRow || itemsHtml || pickupHtml;
        const emptyHtml = !hasContent
            ? `<p class="map-popup-empty">${esc(S.map_no_results)}</p>`
            : '';

        const photoBlock = loc.image_url
            ? `<div class="map-popup-photo">${locationImageHtml(loc.image_url, orgName, 'map-popup-photo-img')}</div>`
            : `<div class="map-popup-photo map-popup-photo-placeholder" style="--popup-accent:${esc(color)}">
                <span class="material-icons">${esc(typeIcon)}</span>
            </div>`;

        return `
            <div class="map-popup" data-location-id="${esc(String(loc.location_id))}">
                <div class="map-popup-drag-zone" title="${esc(S.popup_drag_hint || 'Drag the map to explore')}">
                    <span class="map-popup-drag-grip"></span>
                    <span>${esc(S.popup_drag_hint || 'Drag the map to explore')}</span>
                </div>
                ${photoBlock}
                <div class="map-popup-body">
                    <div class="map-popup-header">
                        <div class="map-popup-header-main">
                            <h3 class="map-popup-title">${esc(orgName)}</h3>
                            ${locName ? `<p class="map-popup-subtitle">${esc(locName)}</p>` : ''}
                        </div>
                        <button type="button" class="map-popup-close-btn" aria-label="${esc(S.popup_close || 'Close')}">
                            <span class="material-icons" aria-hidden="true">close</span>
                        </button>
                    </div>
                    <div class="map-popup-meta">
                        <span class="map-popup-type" style="--popup-accent:${esc(color)}">
                            <span class="material-icons">${esc(typeIcon)}</span>
                            ${esc(typeLabel)}
                        </span>
                        ${verified}
                    </div>
                    <div class="map-popup-scroll">
                        ${offersRow}
                        ${needsRow}
                        ${itemsHtml}
                        ${pickupHtml}
                        ${addressHtml}
                        ${emptyHtml}
                    </div>
                    <div class="map-popup-footer">
                        <button type="button" class="map-popup-details-btn">
                            <span class="material-icons">open_in_new</span>
                            ${esc(S.popup_details_hint || 'Open full details')}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // ── API ──
    async function api(route, options = {}) {
        const url = DS.apiBase + route + (route.includes('?') ? '&' : '?') + 'lang=' + DS.lang;
        const isFormData = options.body instanceof FormData;
        const fetchOpts = { credentials: 'same-origin', ...options };
        if (!isFormData) {
            fetchOpts.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
        }
        const res = await fetch(url, fetchOpts);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Request failed');
        return data;
    }

    // ── Icons ──
    function materialPin(icon, color, size = 36) {
        const badge = Math.round(size * 1.12);
        const iconPx = Math.round(size * 0.62);
        const anchor = Math.round(badge / 2);
        return L.divIcon({
            className: 'custom-pin',
            html: `<span class="map-pin-badge" style="width:${badge}px;height:${badge}px;border-color:${color};color:${color};"><span class="material-icons" style="font-size:${iconPx}px;">${icon}</span></span>`,
            iconSize: [badge, badge],
            iconAnchor: [anchor, anchor],
            popupAnchor: [0, -anchor],
        });
    }

    // ── Geocoder ──
    function mergeGeocodeResults(...lists) {
        const seen = new Set();
        const out = [];
        for (const list of lists) {
            for (const r of list || []) {
                if (!r?.center) continue;
                const key = `${r.center.lat.toFixed(4)},${r.center.lng.toFixed(4)}`;
                if (seen.has(key)) continue;
                seen.add(key);
                out.push(r);
            }
        }
        return out.slice(0, 15);
    }

    const GEO_LANG = {
        en: 'en,pt',
        pt: 'pt-PT,pt,en',
        es: 'es,en',
        fr: 'fr,en',
        de: 'de,en',
        zh: 'zh-CN,zh,en',
        ja: 'ja,en',
    };

    const DATE_LOCALE = {
        en: 'en-GB',
        pt: 'pt-PT',
        es: 'es-ES',
        fr: 'fr-FR',
        de: 'de-DE',
        zh: 'zh-CN',
        ja: 'ja-JP',
    };

    function createCombinedGeocoder() {
        const lang = GEO_LANG[DS.lang] || GEO_LANG.en;
        const nominatim = L.Control.Geocoder.nominatim({
            geocodingQueryParams: {
                limit: 15,
                addressdetails: 1,
                dedupe: 1,
                'accept-language': lang,
            },
        });

        const photon = L.Control.Geocoder.photon
            ? L.Control.Geocoder.photon({
                geocodingQueryParams: {
                    limit: 15,
                    lang: DS.lang === 'pt' ? 'pt' : 'en',
                },
            })
            : null;

        async function queryBoth(q, ctx) {
            const tasks = [nominatim.geocode(q, ctx)];
            if (photon) tasks.push(photon.geocode(q, ctx));
            const settled = await Promise.allSettled(tasks);
            const lists = settled
                .filter((r) => r.status === 'fulfilled')
                .map((r) => r.value);
            return mergeGeocodeResults(...lists);
        }

        return {
            geocode: queryBoth,
            suggest: queryBoth,
            reverse(latLng, scale) {
                if (photon) {
                    return photon.reverse(latLng, scale).catch(() => nominatim.reverse(latLng, scale));
                }
                return nominatim.reverse(latLng, scale);
            },
        };
    }

    function createGeocoder(mapInstance, onResult) {
        if (typeof L.Control.Geocoder === 'undefined') return null;

        const geocoder = L.Control.geocoder({
            geocoder: createCombinedGeocoder(),
            position: 'topleft',
            defaultMarkGeocode: false,
            collapsed: false,
            expand: 'click',
            placeholder: S.search_address || 'Search address…',
            suggestMinLength: 2,
            suggestTimeout: 320,
            showResultIcons: true,
        }).addTo(mapInstance);

        geocoder.on('markgeocode', (e) => {
            const c = e.geocode.center;
            if (onResult) onResult(c, e.geocode);
        });

        return geocoder;
    }

    function layoutMapControls() {
        const corner = document.querySelector('.leaflet-top.leaflet-left');
        if (corner) corner.classList.add('map-controls-cluster');
        updateGeocoderPlaceholder();
    }

    function updateGeocoderPlaceholder() {
        document.querySelectorAll('.leaflet-control-geocoder-form input').forEach((input) => {
            input.placeholder = S.search_address || 'Search address…';
        });
    }

    function setPickLocation(latlng, addressText) {
        if (!pickMap) return;
        if (pickMarker) pickMap.removeLayer(pickMarker);
        pickMarker = L.marker(latlng, { icon: materialPin('location_on', PICK_PIN_COLOR, 42), draggable: true })
            .addTo(pickMap);
        pickMarker.on('dragend', () => {
            const p = pickMarker.getLatLng();
            document.getElementById('pickLat').value = p.lat.toFixed(8);
            document.getElementById('pickLng').value = p.lng.toFixed(8);
        });
        document.getElementById('pickLat').value = latlng.lat.toFixed(8);
        document.getElementById('pickLng').value = latlng.lng.toFixed(8);
        pickMap.setView(latlng, Math.max(pickMap.getZoom(), 14));
        if (addressText && document.getElementById('orgAddress')) {
            document.getElementById('orgAddress').value = addressText;
        }
    }

    // ── Panels ──
    function openPanel(name) {
        const id = panelMap[name];
        if (!id) return;
        const el = document.getElementById(id);
        if (!el) return;

        closeAllPanels(false);
        el.classList.add('open');
        el.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sheet-open');
        activePanel = name;

        document.getElementById('filters')?.classList.add('hidden');
        document.getElementById('mapHint')?.classList.add('hidden');

        if (name === 'list') renderList();
        if (name === 'my') loadMyListings();
        if (name === 'post') {
            setTimeout(() => {
                setDefaultPickupTimes();
                initPickMap();
                const raw = localStorage.getItem('dogeseeds_post_prefs');
                if (raw) {
                    try { prefillOrgForm(JSON.parse(raw)); } catch (_) { /* ignore */ }
                }
            }, 250);
        }
    }

    function closeAllPanels(invalidateMap = true) {
        document.querySelectorAll('.sheet-backdrop').forEach(el => {
            el.classList.remove('open');
            el.setAttribute('aria-hidden', 'true');
        });
        document.body.classList.remove('sheet-open');
        activePanel = null;
        setNavActive('');
        document.getElementById('filters')?.classList.remove('hidden');
        document.getElementById('mapHint')?.classList.remove('hidden');
        if (invalidateMap && map) setTimeout(() => map.invalidateSize(), 150);
    }

    function closeAllPanelsExcept(exceptPanel, invalidateMap = true) {
        document.querySelectorAll('.sheet-backdrop').forEach(el => {
            if (el.id === panelMap[exceptPanel]) return;
            el.classList.remove('open');
            el.setAttribute('aria-hidden', 'true');
        });
        if (!exceptPanel) {
            document.body.classList.remove('sheet-open');
            activePanel = null;
            setNavActive('');
            document.getElementById('filters')?.classList.remove('hidden');
            document.getElementById('mapHint')?.classList.remove('hidden');
        }
        if (invalidateMap && map) setTimeout(() => map.invalidateSize(), 150);
    }

    function setNavActive(panel) {
        document.querySelectorAll('.nav-pill[data-panel], .bottom-nav-item[data-panel]').forEach(p => {
            p.classList.toggle('active', p.dataset.panel === panel);
        });
    }

    function handleNavClick(btn) {
        const panel = btn.dataset.panel;
        document.getElementById('navbarNav')?.classList.remove('open');
        document.getElementById('menuToggle')?.setAttribute('aria-expanded', 'false');
        if (!panel) {
            closeAllPanels();
            return;
        }
        setNavActive(panel);
        openPanel(panel);
    }

    document.querySelectorAll('.nav-pill[data-panel], .bottom-nav-item[data-panel]').forEach(btn => {
        btn.addEventListener('click', () => handleNavClick(btn));
    });

    document.querySelectorAll('[data-close-panel]').forEach(btn => {
        btn.addEventListener('click', closeAllPanels);
    });

    document.querySelectorAll('.sheet-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeAllPanels();
        });
    });

    document.getElementById('menuToggle')?.addEventListener('click', () => {
        const nav = document.getElementById('navbarNav');
        const btn = document.getElementById('menuToggle');
        const open = nav.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    const mobileLangBackdrop = document.getElementById('mobileLangBackdrop');
    const mobileLangSheet = document.getElementById('mobileLangSheet');

    const closeAllLangMenus = () => {
        document.querySelectorAll('.lang-dropdown.open').forEach((dd) => {
            dd.classList.remove('open');
            const menu = dd.querySelector('.lang-menu');
            const toggle = dd.querySelector('[aria-expanded]');
            if (menu) menu.hidden = true;
            toggle?.setAttribute('aria-expanded', 'false');
        });
        mobileLangSheet?.classList.remove('open');
        mobileLangBackdrop?.classList.remove('open');
        mobileLangSheet?.setAttribute('aria-hidden', 'true');
        mobileLangBackdrop?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-lang-open');
        document.getElementById('siteNavbar')?.classList.remove('lang-open');
        document.getElementById('mobileLangToggle')?.setAttribute('aria-expanded', 'false');
    };

    const openMobileLangMenu = () => {
        closeAllLangMenus();
        document.getElementById('mobileLangDropdown')?.classList.add('open');
        mobileLangSheet?.classList.add('open');
        mobileLangBackdrop?.classList.add('open');
        mobileLangSheet?.setAttribute('aria-hidden', 'false');
        mobileLangBackdrop?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-lang-open');
        document.getElementById('mobileLangToggle')?.setAttribute('aria-expanded', 'true');
    };

    const toggleLangMenu = (dropdownId) => {
        const dd = document.getElementById(dropdownId);
        if (!dd) return;

        if (dropdownId === 'mobileLangDropdown') {
            if (mobileLangSheet?.classList.contains('open')) {
                closeAllLangMenus();
            } else {
                openMobileLangMenu();
            }
            return;
        }

        const willOpen = !dd.classList.contains('open');
        closeAllLangMenus();
        if (!willOpen) return;

        dd.classList.add('open');
        const menu = dd.querySelector('.lang-menu');
        const toggle = dd.querySelector('[aria-expanded]');
        if (menu) menu.hidden = false;
        toggle?.setAttribute('aria-expanded', 'true');
        document.getElementById('siteNavbar')?.classList.add('lang-open');
    };

    document.getElementById('langToggle')?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleLangMenu('langDropdown');
    });

    document.getElementById('mobileLangToggle')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleLangMenu('mobileLangDropdown');
    });

    document.querySelectorAll('.lang-menu').forEach((menu) => {
        menu.addEventListener('click', (e) => e.stopPropagation());
    });

    mobileLangSheet?.addEventListener('click', (e) => e.stopPropagation());
    mobileLangBackdrop?.addEventListener('click', () => closeAllLangMenus());

    document.addEventListener('click', (e) => {
        if (e.target.closest('#mobileLangToggle, #mobileLangSheet, #mobileLangBackdrop, #langDropdown, #langMenu, #langToggle')) {
            return;
        }
        closeAllLangMenus();
    });

    // ── Filters ──
    document.querySelectorAll('.filter-pill').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentCategory = chip.dataset.category || '';
            loadMapData();
            if (activePanel === 'list') renderList();
        });
    });

    document.getElementById('btnNearby')?.addEventListener('click', () => {
        const btn = document.getElementById('btnNearby');
        btn?.classList.add('loading');
        getUserLocation(true).then(() => loadMapData()).finally(() => btn?.classList.remove('loading'));
    });

    document.getElementById('listSearch')?.addEventListener('input', (e) => {
        listSearchQuery = e.target.value;
        clearTimeout(listSearchTimer);
        listSearchTimer = setTimeout(() => {
            if (activePanel === 'list') renderList();
        }, 200);
    });

    function getUserLocation(centerMap = false) {
        return new Promise((resolve) => {
            if (!navigator.geolocation) { resolve(null); return; }
            navigator.geolocation.getCurrentPosition(
                pos => {
                    userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    if (centerMap && map) map.setView([userLocation.lat, userLocation.lng], 13);
                    resolve(userLocation);
                },
                () => resolve(null),
                { enableHighAccuracy: true, timeout: 8000 }
            );
        });
    }

    // ── Map ──
    function initMap() {
        const defaults = DS.mapDefaults;
        map = L.map('map', { zoomControl: true }).setView([defaults.lat, defaults.lng], defaults.zoom);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://openstreetmap.org">OSM</a> &copy; <a href="https://carto.com">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

        createGeocoder(map, (center) => {
            map.setView(center, 14);
            userLocation = { lat: center.lat, lng: center.lng };
            loadMapData();
        });

        map.whenReady(layoutMapControls);
        setTimeout(layoutMapControls, 0);
        setupMapPopupBehavior();
        loadMapData();
    }

    function setupMapPopupBehavior() {
        if (map._popupBehaviorBound) return;
        map._popupBehaviorBound = true;

        map.on('popupopen', (e) => {
            const el = e.popup.getElement();
            if (!el) return;

            const scroll = el.querySelector('.map-popup-scroll');
            if (scroll) {
                const updateScrollState = () => {
                    const overflow = scroll.scrollHeight > scroll.clientHeight + 4;
                    scroll.classList.toggle('has-overflow', overflow);
                    scroll.classList.toggle('at-bottom', !overflow || scroll.scrollTop + scroll.clientHeight >= scroll.scrollHeight - 6);
                };
                updateScrollState();
                scroll.addEventListener('scroll', updateScrollState, { passive: true });
            }

            const locId = el.querySelector('.map-popup')?.dataset.locationId;
            const loc = locId ? locations.find(l => String(l.location_id) === locId) : null;

            const closeBtn = el.querySelector('.map-popup-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', (ev) => {
                    L.DomEvent.stopPropagation(ev);
                    map.closePopup();
                });
            }

            const detailBtn = el.querySelector('.map-popup-details-btn');
            if (detailBtn && loc) {
                detailBtn.addEventListener('click', (ev) => {
                    L.DomEvent.stopPropagation(ev);
                    showDetail(loc, { focusMap: false });
                });
            }
        });
    }

    function stopSplashLogoRotation() {
        if (splashLogoInterval) {
            clearInterval(splashLogoInterval);
            splashLogoInterval = null;
        }
    }

    function showSplashLogo() {
        const logoImg = document.getElementById('splashCardImg');
        const iconWrap = document.getElementById('splashCardIconWrap');
        const iconEl = document.getElementById('splashCardIcon');
        const badge = document.getElementById('splashCard');
        logoImg?.classList.remove('is-hidden');
        iconWrap?.classList.remove('is-visible');
        iconWrap?.setAttribute('hidden', '');
        iconEl?.classList.remove('fading');
        badge?.style.setProperty('--brand-icon-color', '#4CAF50');
    }

    function initSplashLogoRotation() {
        const logoImg = document.getElementById('splashCardImg');
        const badge = document.getElementById('splashCard');
        const iconWrap = document.getElementById('splashCardIconWrap');
        const iconEl = document.getElementById('splashCardIcon');
        const cycle = DS.brandLogoCycle || [];
        if (!logoImg || !badge || !iconWrap || !iconEl || !cycle.length) return;

        let iconIdx = 0;
        let showingLogo = true;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const stepMs = reducedMotion ? 2200 : 1500;

        const applyIcon = (i, spin) => {
            const item = cycle[i];
            iconEl.textContent = item.icon;
            badge.style.setProperty('--brand-icon-color', item.color);
            if (spin) {
                badge.classList.remove('is-spinning');
                void badge.offsetWidth;
                badge.classList.add('is-spinning');
            }
        };

        const showIcon = (i, spin) => {
            applyIcon(i, spin);
            logoImg.classList.add('is-hidden');
            iconWrap.removeAttribute('hidden');
            iconWrap.classList.add('is-visible');
            showingLogo = false;
        };

        showSplashLogo();

        splashLogoInterval = setInterval(() => {
            if (showingLogo) {
                if (reducedMotion) {
                    showIcon(iconIdx, false);
                    return;
                }
                logoImg.classList.add('is-hidden');
                window.setTimeout(() => showIcon(iconIdx, true), 140);
                return;
            }

            if (reducedMotion) {
                showSplashLogo();
                iconIdx = (iconIdx + 1) % cycle.length;
                showingLogo = true;
                return;
            }

            iconEl.classList.add('fading');
            window.setTimeout(() => {
                showSplashLogo();
                iconIdx = (iconIdx + 1) % cycle.length;
                showingLogo = true;
            }, 220);
        }, stepMs);
    }

    function playSplashExit() {
        if (splashExitPromise) return splashExitPromise;

        splashExitPromise = new Promise((resolve) => {
            const splash = document.getElementById('splashScreen');
            const card = document.getElementById('splashCard');
            const cardImg = document.getElementById('splashCardImg');
            const brand = document.getElementById('siteBrand');

            stopSplashLogoRotation();
            showSplashLogo();

            if (!splash || !card || !cardImg || !brand) {
                splash?.classList.add('hidden');
                brand?.classList.add('logo-ready');
                splashActive = false;
                window.dispatchEvent(new Event('dogeseeds-splash-done'));
                resolve();
                return;
            }

            const run = () => {
                const cardRect = card.getBoundingClientRect();
                const brandRect = brand.getBoundingClientRect();

                const startX = cardRect.left + cardRect.width / 2;
                const startY = cardRect.top + cardRect.height / 2;
                const endX = brandRect.left + brandRect.width / 2;
                const endY = brandRect.top + brandRect.height / 2;

                const dx = endX - startX;
                const dy = endY - startY;
                const scale = Math.min(
                    (brandRect.width * 0.92) / cardRect.width,
                    (brandRect.height * 1.15) / cardRect.height,
                    0.42
                );

                splash.classList.add('exiting');
                card.classList.add('fly-to-logo');
                card.style.transition = 'transform 0.85s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.35s ease 0.55s, border-radius 0.85s ease, box-shadow 0.85s ease';
                card.style.transform = `translate(${dx}px, ${dy}px) scale(${scale})`;

                window.setTimeout(() => {
                    card.style.opacity = '0';
                }, 620);

                window.setTimeout(() => {
                    splash.classList.add('hidden');
                    splash.setAttribute('aria-hidden', 'true');
                    brand.classList.add('logo-ready');
                    card.style.transition = '';
                    card.style.transform = '';
                    card.style.opacity = '';
                    card.classList.remove('fly-to-logo');
                    splash.classList.remove('exiting');
                    splashActive = false;
                    document.getElementById('mapOverlay')?.classList.add('hidden');
                    window.dispatchEvent(new Event('dogeseeds-splash-done'));
                    resolve();
                }, 980);
            };

            if (cardImg.complete && cardImg.naturalWidth) {
                requestAnimationFrame(() => requestAnimationFrame(run));
            } else {
                cardImg.addEventListener('load', () => requestAnimationFrame(run), { once: true });
            }
        });

        return splashExitPromise;
    }

    async function loadMapData() {
        let route = 'map';
        const params = new URLSearchParams();
        if (currentCategory) params.set('category', currentCategory);
        if (userLocation) {
            params.set('lat', userLocation.lat);
            params.set('lng', userLocation.lng);
            params.set('radius', '30');
        }
        const qs = params.toString();
        if (qs) route += '?' + qs;

        const isInitialLoad = splashActive;

        try {
            const requests = [api(route)];
            if (isInitialLoad) {
                requests.push(new Promise((r) => setTimeout(r, 2800)));
            }

            const [data] = await Promise.all(requests);
            locations = data.locations || [];
            updateFilterCounts(data.category_counts || {});
            renderMarkers();

            if (isInitialLoad) {
                await playSplashExit();
            } else {
                document.getElementById('mapOverlay')?.classList.add('hidden');
            }

            openSharedListing();
        } catch (e) {
            console.error(e);
            if (isInitialLoad) {
                await playSplashExit();
            }
            const overlay = document.getElementById('mapOverlay');
            if (overlay) {
                overlay.textContent = S.map_no_results || 'Error';
                overlay.classList.remove('hidden');
            }
        }
    }

    function clearMarkerHighlight() {
        if (!highlightedMarker) return;
        highlightedMarker.getElement()?.querySelector('.map-pin-badge')?.classList.remove('marker-highlighted');
        highlightedMarker = null;
    }

    function focusMapOnLocation(loc, { openPopup = true } = {}) {
        if (!map || !loc) return;

        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        const targetZoom = Math.max(map.getZoom(), 13);
        map.flyTo([lat, lng], targetZoom, { duration: 0.65 });

        clearMarkerHighlight();
        const marker = markerByLocationId.get(loc.location_id);
        if (!marker) return;

        highlightedMarker = marker;
        marker.getElement()?.querySelector('.map-pin-badge')?.classList.add('marker-highlighted');

        if (openPopup) {
            window.setTimeout(() => marker.openPopup(), 450);
        }
    }

    function renderMarkers() {
        markers.forEach(m => map.removeLayer(m));
        markers = [];
        markerByLocationId.clear();
        clearMarkerHighlight();

        locations.forEach(loc => {
            const color = loc.marker_color || '#4CAF50';
            const icon = materialPin(loc.icon || 'place', color, 36);

            const marker = L.marker([loc.latitude, loc.longitude], { icon })
                .addTo(map)
                .bindPopup(buildMapPopup(loc), {
                    maxWidth: 268,
                    minWidth: 248,
                    className: 'map-popup-leaflet',
                    autoPan: true,
                    autoPanPadding: [24, 24],
                    closeButton: false,
                    closeOnClick: false,
                    autoClose: true,
                });

            markers.push(marker);
            markerByLocationId.set(loc.location_id, marker);
        });
    }

    function locationImageHtml(url, alt, className = 'location-photo') {
        if (!url) return '';
        return `<img src="${esc(url)}" alt="${esc(alt || '')}" class="${className}" loading="lazy">`;
    }

    function categoryBadges(items, prefix) {
        if (!items || !items.length) return '';
        return `<p><strong>${esc(prefix)}:</strong> ${items.map(c =>
            `<span class="badge badge-${c}">${esc(S['filter_' + c] || c)}</span>`
        ).join(' ')}</p>`;
    }

    function formatLocationAddress(loc) {
        return [loc.address, loc.city, loc.country].filter(Boolean).join(', ');
    }

    function getListingShareUrls(loc) {
        const slug = loc.slug;
        if (!slug) return [];
        const base = (DS.siteUrl || window.location.origin).replace(/\/$/, '');
        const urls = [];
        (loc.offers || []).forEach(cat => {
            urls.push({
                intent: 'giving',
                category: cat,
                label: `${S.share_giving || 'Giving'} · ${S['filter_' + cat] || cat}`,
                url: `${base}/${encodeURIComponent(slug)}/giving/${cat}`,
            });
        });
        (loc.needs || []).forEach(cat => {
            urls.push({
                intent: 'needing',
                category: cat,
                label: `${S.share_needing || 'Needing'} · ${S['filter_' + cat] || cat}`,
                url: `${base}/${encodeURIComponent(slug)}/needing/${cat}`,
            });
        });
        return urls;
    }

    async function copyShareUrl(url) {
        try {
            await navigator.clipboard.writeText(url);
            showToast(S.share_copy || 'Link copied!', 'success');
        } catch {
            showToast(url, 'info');
        }
    }

    async function shareListing(loc) {
        const urls = getListingShareUrls(loc);
        if (!urls.length) {
            showToast(S.share_unavailable || 'Cannot share this listing yet', 'error');
            return;
        }
        const primary = urls[0];
        const title = loc.location_name || loc.org_name;
        if (navigator.share) {
            try {
                await navigator.share({
                    title,
                    text: primary.label,
                    url: primary.url,
                });
                return;
            } catch (err) {
                if (err?.name === 'AbortError') return;
            }
        }
        await copyShareUrl(primary.url);
    }

    function renderShareSection(loc) {
        const urls = getListingShareUrls(loc);
        if (!urls.length) return '';

        const primary = urls[0];
        const multi = urls.length > 1;

        const optionRows = urls.map(item => `
            <div class="detail-share-option">
                <span class="detail-share-option-label">
                    <span class="material-icons">${item.intent === 'giving' ? 'redeem' : 'volunteer_activism'}</span>
                    <span>${esc(item.label)}</span>
                </span>
                <button type="button" class="btn btn-sm btn-outline detail-share-copy-btn" data-share-url="${esc(item.url)}" title="${esc(S.share_copy_link || 'Copy link')}">
                    <span class="material-icons">content_copy</span>
                </button>
            </div>
        `).join('');

        return `
            <section class="detail-share-section" aria-labelledby="detailShareTitle">
                <button type="button" class="detail-share-toggle" id="detailShareToggle" aria-expanded="false" aria-controls="detailSharePanel">
                    <span class="detail-share-toggle-main">
                        <span class="material-icons">share</span>
                        <span id="detailShareTitle">${esc(S.share_listing || 'Share listing')}</span>
                    </span>
                    <span class="material-icons detail-share-chevron">expand_more</span>
                </button>
                <div class="detail-share-panel" id="detailSharePanel" hidden>
                    <p class="detail-share-hint">${esc(S.share_listing_hint || 'Share a link with the listing photo on social media.')}</p>
                    <label class="detail-share-field-label" for="detailShareUrlInput">${esc(S.share_main_link || 'Main link')}</label>
                    <div class="detail-share-primary">
                        <input type="text" class="detail-share-url-input" id="detailShareUrlInput" readonly value="${esc(primary.url)}">
                        <div class="detail-share-primary-actions">
                            <button type="button" class="btn btn-sm" id="detailShareCopyPrimary" data-share-url="${esc(primary.url)}">
                                <span class="material-icons">content_copy</span>
                                ${esc(S.share_copy_link || 'Copy link')}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline" id="detailShareBtn">
                                <span class="material-icons">ios_share</span>
                                ${esc(S.share_native || 'Share')}
                            </button>
                        </div>
                    </div>
                    ${multi ? `
                        <p class="detail-share-options-title">${esc(S.share_other_links || 'Links by category')}</p>
                        <div class="detail-share-options">${optionRows}</div>
                    ` : ''}
                </div>
            </section>
        `;
    }

    function bindShareSection(loc) {
        const toggle = document.getElementById('detailShareToggle');
        const panel = document.getElementById('detailSharePanel');
        toggle?.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            panel.hidden = open;
            toggle.classList.toggle('open', !open);
        });

        document.getElementById('detailShareCopyPrimary')?.addEventListener('click', (e) => {
            copyShareUrl(e.currentTarget.dataset.shareUrl || '');
        });
        document.getElementById('detailShareBtn')?.addEventListener('click', () => shareListing(loc));
        document.querySelectorAll('.detail-share-copy-btn').forEach(btn => {
            btn.addEventListener('click', () => copyShareUrl(btn.dataset.shareUrl || ''));
        });
    }

    function openSharedListing() {
        const id = DS.shareLocationId;
        if (!id || DS._shareOpened) return;
        const loc = locations.find(l => l.location_id == id);
        if (!loc) return;
        DS._shareOpened = true;
        showDetail(loc, { focusMap: true });
    }

    function detailInfoCard(icon, label, contentHtml) {
        return `<div class="info-card">
            <div class="info-card-icon"><span class="material-icons">${icon}</span></div>
            <div>
                ${label ? `<strong>${esc(label)}</strong>` : ''}
                ${contentHtml}
            </div>
        </div>`;
    }

    function showDetail(loc, { focusMap = true } = {}) {
        if (focusMap) focusMapOnLocation(loc);

        document.getElementById('detailTitleText').textContent = loc.location_name || loc.org_name;
        let sub = loc.org_name;
        if (loc.location_name && loc.location_name !== loc.org_name) {
            sub = `${loc.org_name} · ${loc.location_name}`;
        }
        sub += ' · ' + (S['org_' + loc.org_type] || loc.org_type);
        if (loc.org_verified == 1) sub += ' · ✓ ' + S.verified;
        document.getElementById('detailSubtitle').textContent = sub;

        const addressLine = formatLocationAddress(loc);
        let infoHtml = '';

        if (loc.org_description) {
            infoHtml += detailInfoCard('info', '', `<p class="info-card-desc">${esc(loc.org_description)}</p>`);
        }
        if (addressLine) {
            infoHtml += detailInfoCard('place', S.address || 'Address', `<p class="info-card-desc">${esc(addressLine)}</p>`);
        }
        if (loc.instructions) {
            infoHtml += detailInfoCard('info', S.instructions || 'Instructions', `<p class="info-card-desc">${esc(loc.instructions)}</p>`);
        }
        if (loc.org_website) {
            const url = loc.org_website.startsWith('http') ? loc.org_website : `https://${loc.org_website}`;
            infoHtml += detailInfoCard('language', S.website || 'Website',
                `<p class="info-card-desc"><a href="${esc(url)}" target="_blank" rel="noopener noreferrer">${esc(loc.org_website)}</a></p>`);
        }
        if (loc.contact_email || loc.contact_phone) {
            let contactBody = '';
            if (loc.contact_email) {
                contactBody += `<p class="info-card-desc"><a href="mailto:${esc(loc.contact_email)}">${esc(loc.contact_email)}</a></p>`;
            }
            if (loc.contact_phone) {
                contactBody += `<p class="info-card-desc"><a href="tel:${esc(loc.contact_phone)}">${esc(loc.contact_phone)}</a></p>`;
            }
            infoHtml += detailInfoCard('contact_phone', S.contact || 'Contact', contactBody);
        }

        const items = (loc.donations || []).map(d => `
            <div class="detail-item">
                <h4>${esc(d.title)}</h4>
                ${d.description ? '<p>' + esc(d.description) + '</p>' : ''}
                <div class="meta">
                    <span class="badge badge-${d.category}">${esc(S['filter_' + d.category] || d.category)}</span>
                    ${d.quantity ? '<span>' + esc(S.quantity) + ': ' + esc(d.quantity) + '</span>' : ''}
                    <span>${esc(S.pickup_window)}: ${formatDate(d.pickup_start)} to ${formatDate(d.pickup_end)}</span>
                </div>
                ${DS.user ? `<button class="btn btn-sm btn-block" onclick="window.reserveItem(${d.id})">${esc(S.reserve)}</button>` : ''}
            </div>
        `).join('');

        const detailPhoto = loc.image_url
            ? `<div class="detail-photo">${locationImageHtml(loc.image_url, loc.org_name, 'detail-photo-img')}</div>`
            : '';

        document.getElementById('detailContent').innerHTML = `
            ${detailPhoto}
            ${categoryBadges(loc.offers, S.we_offer)}
            ${categoryBadges(loc.needs, S.we_need)}
            ${infoHtml}
            <h3 style="font-size:0.9rem;margin:12px 0 8px;color:var(--muted);">${esc(S.available_items)}</h3>
            ${items || `<div class="empty-state"><span class="material-icons">inventory_2</span><p>${esc(S.map_no_results)}</p></div>`}
            ${renderShareSection(loc)}
            <section class="inquiry-section" aria-labelledby="inquiryTitle">
                <h3 class="inquiry-title" id="inquiryTitle">
                    <span class="material-icons">mail</span>
                    ${esc(S.inquiry_title || 'Contact this listing')}
                </h3>
                <p class="inquiry-hint">
                    <span class="material-icons">info</span>
                    ${esc(S.inquiry_hint || 'Include your contact details so they can reply.')}
                </p>
                <form id="inquiryForm" class="inquiry-form" data-location-id="${loc.location_id}">
                    <div class="inquiry-field">
                        <label for="inquiryName">${esc(S.inquiry_name || 'Your name')}</label>
                        <input type="text" name="name" id="inquiryName" autocomplete="name" placeholder="${esc(S.inquiry_name || 'Your name')}">
                    </div>
                    <div class="inquiry-field">
                        <label for="inquiryEmail">${esc(S.inquiry_email || 'Your email')}</label>
                        <input type="email" name="email" id="inquiryEmail" autocomplete="email" placeholder="${esc(S.inquiry_email || 'Your email')}">
                    </div>
                    <div class="inquiry-field">
                        <label for="inquiryMessage">${esc(S.inquiry_message || 'Your message')}</label>
                        <textarea name="message" id="inquiryMessage" rows="4" required minlength="10" placeholder="${esc(S.inquiry_message_placeholder || 'Include how to reach you…')}"></textarea>
                    </div>
                    <div class="slide-submit" id="inquirySlideSubmit">
                        <span class="track-text">${esc(S.slide_submit_inquiry || 'Slide to send message')}</span>
                        <div class="thumb"><span class="material-icons">chevron_right</span></div>
                    </div>
                </form>
            </section>
        `;

        bindSlideSubmit(document.getElementById('inquirySlideSubmit'), submitInquiryForm, (fn) => { resetInquirySlide = fn; });
        bindShareSection(loc);

        const fromList = activePanel === 'list';
        if (fromList) {
            closeAllPanelsExcept('detail', false);
            const el = document.getElementById('detailPanel');
            el?.classList.add('open');
            el?.setAttribute('aria-hidden', 'false');
            document.body.classList.add('sheet-open');
            activePanel = 'detail';
            setNavActive('list');
            document.getElementById('filters')?.classList.add('hidden');
            document.getElementById('mapHint')?.classList.add('hidden');
        } else {
            openPanel('detail');
        }
    }

    window.reserveItem = async function (donationId) {
        const ok = await showConfirm(S.confirm_reserve || 'Reserve this item?', {
            desc: S.confirm_reserve_desc || '',
            icon: 'event_available',
        });
        if (!ok) return;
        try {
            await api('reservations', {
                method: 'POST',
                body: JSON.stringify({ donation_id: donationId }),
            });
            showToast(S.reserved_success || S.reserved || 'Reserved!', 'success');
            loadMapData();
            closeAllPanels();
        } catch (e) {
            showToast(e.message, 'error');
        }
    };

    window.showLocDetail = function (locationId) {
        const loc = locations.find(l => l.location_id == locationId);
        if (!loc) return;

        document.querySelectorAll('.donation-card').forEach(card => {
            card.classList.toggle('active', card.dataset.locationId == locationId);
        });

        showDetail(loc, { focusMap: true });
    };

    // ── List ──
    function updateFilterCounts(counts) {
        document.querySelectorAll('.filter-count').forEach(el => {
            const key = el.dataset.countFor || '';
            const n = counts[key] ?? 0;
            el.textContent = String(n);
            el.classList.toggle('is-zero', n === 0);
        });
    }

    function locationMatchesSearch(loc, query) {
        if (!query) return true;
        const q = query.toLowerCase();
        const fields = [
            loc.org_name,
            loc.location_name,
            loc.city,
            loc.address,
            loc.country,
            loc.instructions,
            loc.org_description,
        ];
        if (fields.some(v => v && String(v).toLowerCase().includes(q))) return true;
        const cats = [...(loc.offers || []), ...(loc.needs || [])];
        if (cats.some(c => (S['filter_' + c] || c).toLowerCase().includes(q) || c.includes(q))) return true;
        return (loc.donations || []).some(d =>
            [d.title, d.description, d.category, S['filter_' + d.category]]
                .filter(Boolean)
                .some(v => String(v).toLowerCase().includes(q))
        );
    }

    function getFilteredListLocations() {
        if (!listSearchQuery.trim()) return locations;
        return locations.filter(loc => locationMatchesSearch(loc, listSearchQuery.trim()));
    }

    function renderList() {
        const container = document.getElementById('donationList');
        const filtered = getFilteredListLocations();

        if (!filtered.length) {
            container.innerHTML = `<div class="empty-state">
                <span class="material-icons">inventory_2</span>
                <p>${esc(listSearchQuery.trim() ? (S.list_search_no_results || S.map_no_results) : S.map_no_results)}</p>
            </div>`;
            return;
        }

        container.innerHTML = filtered.map(loc => {
            const donations = loc.donations || [];
            const title = donations.length ? donations[0].title : loc.org_name;
            const desc = donations.length ? donations[0].description : '';
            const offers = (loc.offers || []).map(c => `<span class="badge badge-${c}">${esc(S['filter_' + c] || c)}</span>`).join('');
            const needs = (loc.needs || []).map(c => `<span class="badge badge-${c}">${esc(S['filter_' + c] || c)}</span>`).join('');
            const pickup = pickupWindowLabel(loc);

            const cardPhoto = loc.image_url
                ? `<div class="donation-card-photo">${locationImageHtml(loc.image_url, loc.org_name, 'donation-card-img')}</div>`
                : '';

            return `
            <div class="donation-card${loc.image_url ? ' has-photo' : ''}" data-location-id="${loc.location_id}" onclick="window.showLocDetail(${loc.location_id})">
                ${cardPhoto}
                <div class="donation-card-body">
                    <h3>${esc(title)}</h3>
                    ${desc ? '<p style="font-size:0.85rem;color:var(--muted);">' + esc(desc) + '</p>' : ''}
                    <div class="meta">
                        <span>${esc(loc.org_name)}</span>
                        ${offers ? '<span>' + esc(S.we_offer) + ': ' + offers + '</span>' : ''}
                        ${needs ? '<span>' + esc(S.we_need) + ': ' + needs + '</span>' : ''}
                        ${pickup ? '<span><span class="material-icons" style="font-size:14px;vertical-align:-2px;">schedule</span> ' + esc(pickup) + '</span>' : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // ── My listings ──
    function donationStatusLabel(status) {
        const labels = {
            available: S.my_status_available || 'Available',
            reserved: S.reserved || 'Reserved',
            collected: S.my_status_collected || 'Collected',
            expired: S.my_status_expired || 'Expired',
        };
        return labels[status] || status;
    }

    function toDatetimeLocalFromIso(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '';
        return toDatetimeLocalValue(d);
    }

    function showMyListingsList() {
        editingListingId = null;
        document.getElementById('myListingsView')?.classList.remove('hidden');
        document.getElementById('editListingForm')?.classList.add('hidden');
        const sub = document.getElementById('myPanelSubtitle');
        if (sub) sub.textContent = S.my_listings_subtitle || '';
    }

    function showEditListing(listing) {
        editingListingId = listing.location_id;
        document.getElementById('myListingsView')?.classList.add('hidden');
        document.getElementById('editListingForm')?.classList.remove('hidden');
        const sub = document.getElementById('myPanelSubtitle');
        if (sub) sub.textContent = S.my_edit_subtitle || '';

        document.getElementById('editLocationId').value = listing.location_id;
        document.getElementById('editListingTitle').textContent = listing.org_name || listing.location_name;
        document.getElementById('editDescription').value = listing.org_description || '';
        document.getElementById('editInstructions').value = listing.instructions || '';
        document.getElementById('editAddress').value = listing.address || '';
        document.getElementById('editCity').value = listing.city || '';
        document.getElementById('editCountry').value = listing.country || '';
        document.getElementById('editContactEmail').value = listing.contact_email || '';
        document.getElementById('editContactPhone').value = listing.contact_phone || '';
        document.getElementById('editShowContact').checked = !!Number(listing.show_contact_public);

        const donations = (listing.donations || []).filter(d => ['available', 'reserved'].includes(d.status));
        const ref = donations[0];
        document.getElementById('editPickupStart').value = toDatetimeLocalFromIso(ref?.pickup_start);
        document.getElementById('editPickupEnd').value = toDatetimeLocalFromIso(ref?.pickup_end);
    }

    function renderMyListings() {
        const container = document.getElementById('myListingsList');
        if (!container) return;

        if (!myListings.length) {
            container.innerHTML = `<div class="empty-state">
                <span class="material-icons">inventory_2</span>
                <p>${esc(S.my_listings_empty || 'No listings yet')}</p>
                ${DS.canPost ? `<button type="button" class="btn" id="myListingsAddBtn">${esc(S.post_item || 'Add place')}</button>` : ''}
            </div>`;
            document.getElementById('myListingsAddBtn')?.addEventListener('click', () => {
                closeAllPanels();
                setNavActive('post');
                openPanel('post');
            });
            return;
        }

        container.innerHTML = myListings.map((listing) => {
            const inactive = !Number(listing.active);
            const pickup = pickupWindowLabel(listing);
            const offers = (listing.offers || []).map(c => `<span class="badge badge-${c}">${esc(S['filter_' + c] || c)}</span>`).join('');
            const needs = (listing.needs || []).map(c => `<span class="badge badge-${c}">${esc(S['filter_' + c] || c)}</span>`).join('');
            const items = (listing.donations || []).map(d => `
                <div class="my-item-row">
                    <div class="my-item-info">
                        <span class="badge badge-${d.category}">${esc(S['filter_' + d.category] || d.category)}</span>
                        <span class="my-item-status status-${d.status}">${esc(donationStatusLabel(d.status))}</span>
                    </div>
                    ${d.status === 'available' || d.status === 'reserved' ? `
                    <button type="button" class="btn btn-sm btn-outline my-item-collect" data-donation-id="${d.id}">
                        <span class="material-icons">check_circle</span>${esc(S.my_mark_collected || 'Collected')}
                    </button>` : (d.status === 'collected' ? `
                    <button type="button" class="btn btn-sm btn-outline my-item-restore" data-donation-id="${d.id}">
                        <span class="material-icons">refresh</span>${esc(S.my_mark_available || 'Available')}
                    </button>` : '')}
                </div>
            `).join('');

            return `
            <div class="my-listing-card${inactive ? ' is-inactive' : ''}" data-location-id="${listing.location_id}">
                <div class="my-listing-header">
                    <h3>${esc(listing.org_name)}</h3>
                    <span class="my-listing-badge${inactive ? ' inactive' : ' active'}">${esc(inactive ? (S.my_listing_hidden || 'Hidden') : (S.my_listing_live || 'On map'))}</span>
                </div>
                ${listing.org_description ? `<p class="my-listing-desc">${esc(listing.org_description)}</p>` : ''}
                <div class="my-listing-meta">
                    ${offers ? `<span>${esc(S.we_offer)}: ${offers}</span>` : ''}
                    ${needs ? `<span>${esc(S.we_need)}: ${needs}</span>` : ''}
                    ${pickup ? `<span><span class="material-icons">schedule</span> ${esc(pickup)}</span>` : ''}
                </div>
                ${items ? `<div class="my-items-list">${items}</div>` : ''}
                <div class="my-listing-actions">
                    ${!inactive ? `<button type="button" class="btn btn-sm btn-outline my-listing-map" data-location-id="${listing.location_id}">
                        <span class="material-icons">map</span>${esc(S.nav_map || 'Map')}
                    </button>` : ''}
                    ${!inactive ? `<button type="button" class="btn btn-sm btn-outline my-listing-edit" data-location-id="${listing.location_id}">
                        <span class="material-icons">edit</span>${esc(S.my_edit || 'Edit')}
                    </button>` : ''}
                    ${!inactive ? `<button type="button" class="btn btn-sm btn-outline my-listing-remove" data-location-id="${listing.location_id}">
                        <span class="material-icons">visibility_off</span>${esc(S.my_remove || 'Remove')}
                    </button>` : ''}
                </div>
            </div>`;
        }).join('');

        container.querySelectorAll('.my-listing-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = Number(btn.dataset.locationId);
                const listing = myListings.find(l => l.location_id == id);
                if (listing) showEditListing(listing);
            });
        });

        container.querySelectorAll('.my-listing-map').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = Number(btn.dataset.locationId);
                const loc = locations.find(l => l.location_id == id) || myListings.find(l => l.location_id == id);
                if (!loc) return;
                closeAllPanels();
                focusMapOnLocation(loc);
            });
        });

        container.querySelectorAll('.my-listing-remove').forEach(btn => {
            btn.addEventListener('click', () => removeMyListing(Number(btn.dataset.locationId)));
        });

        container.querySelectorAll('.my-item-collect').forEach(btn => {
            btn.addEventListener('click', () => updateDonationStatus(Number(btn.dataset.donationId), 'collected'));
        });

        container.querySelectorAll('.my-item-restore').forEach(btn => {
            btn.addEventListener('click', () => updateDonationStatus(Number(btn.dataset.donationId), 'available'));
        });
    }

    async function loadMyListings() {
        if (!DS.user) return;
        showMyListingsList();
        try {
            const data = await api('my/listings');
            myListings = data.listings || [];
            renderMyListings();
        } catch (e) {
            showToast(e.message, 'error');
        }
    }

    async function removeMyListing(locationId) {
        const ok = await showConfirm(S.my_remove_confirm || 'Remove from map?', {
            desc: S.my_remove_confirm_desc || '',
            icon: 'visibility_off',
        });
        if (!ok) return;
        try {
            await api('my/listings', {
                method: 'DELETE',
                body: JSON.stringify({ location_id: locationId }),
            });
            showToast(S.my_removed || 'Removed from map', 'success');
            await loadMyListings();
            loadMapData();
        } catch (e) {
            showToast(e.message, 'error');
        }
    }

    async function updateDonationStatus(donationId, status) {
        try {
            const data = await api('my/listings', {
                method: 'PATCH',
                body: JSON.stringify({ donation_id: donationId, status }),
            });
            if (data.listing) {
                const idx = myListings.findIndex(l => l.location_id == data.listing.location_id);
                if (idx >= 0) myListings[idx] = data.listing;
            }
            renderMyListings();
            loadMapData();
            showToast(S.my_item_updated || 'Item updated', 'success');
        } catch (e) {
            showToast(e.message, 'error');
        }
    }

    document.getElementById('btnMyListingsMobileTop')?.addEventListener('click', () => {
        setNavActive('my');
        openPanel('my');
    });

    document.getElementById('btnCancelEdit')?.addEventListener('click', () => {
        showMyListingsList();
        renderMyListings();
    });

    document.getElementById('editListingForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const pickupStart = fd.get('pickup_start');
        const pickupEnd = fd.get('pickup_end');
        if (!pickupStart || !pickupEnd || new Date(String(pickupEnd)) <= new Date(String(pickupStart))) {
            showToast(S.pickup_end_before_start || 'Invalid pickup window', 'error');
            return;
        }
        try {
            await api('my/listings', {
                method: 'PUT',
                body: JSON.stringify({
                    location_id: Number(fd.get('location_id')),
                    description: fd.get('description'),
                    instructions: fd.get('instructions'),
                    address: fd.get('address'),
                    city: fd.get('city'),
                    country: fd.get('country'),
                    contact_email: fd.get('contact_email'),
                    contact_phone: fd.get('contact_phone'),
                    show_contact_public: fd.get('show_contact_public') ? 1 : 0,
                    pickup_start: pickupStart,
                    pickup_end: pickupEnd,
                }),
            });
            showToast(S.my_saved || 'Changes saved', 'success');
            await loadMyListings();
            loadMapData();
            showMyListingsList();
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    // ── Auth ──
    const openAuth = () => {
        showAuthView('login');
        openPanel('auth');
    };
    document.getElementById('btnLogin')?.addEventListener('click', openAuth);
    document.getElementById('btnLoginMobile')?.addEventListener('click', openAuth);
    document.getElementById('btnLoginMobileTop')?.addEventListener('click', openAuth);

    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.auth-tab').forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            const isLogin = tab.dataset.auth === 'login';
            document.getElementById('loginForm')?.classList.toggle('hidden', !isLogin);
            document.getElementById('registerForm')?.classList.toggle('hidden', isLogin);
            document.getElementById('forgotForm')?.classList.add('hidden');
            document.getElementById('authPanelTitle').textContent = isLogin ? S.nav_login : S.nav_register;
            const iconEl = document.getElementById('authPanelIcon');
            if (iconEl) iconEl.textContent = isLogin ? 'login' : 'person_add';
            const subEl = document.getElementById('authPanelSubtitle');
            if (subEl) subEl.textContent = isLogin ? (S.auth_login_subtitle || '') : (S.auth_register_subtitle || '');
        });
    });

    function showAuthView(view) {
        const isLogin = view === 'login';
        const isForgot = view === 'forgot';
        document.querySelectorAll('.auth-tab').forEach(t => {
            const loginTab = t.dataset.auth === 'login';
            t.classList.toggle('active', isLogin ? loginTab : !loginTab);
            t.setAttribute('aria-selected', isLogin ? String(loginTab) : String(!loginTab));
        });
        document.getElementById('loginForm')?.classList.toggle('hidden', !isLogin || isForgot);
        document.getElementById('registerForm')?.classList.toggle('hidden', isLogin || isForgot);
        document.getElementById('forgotForm')?.classList.toggle('hidden', !isForgot);
        document.getElementById('authPanelTitle').textContent = isForgot
            ? (S.password_forgot || 'Forgot password?')
            : (isLogin ? S.nav_login : S.nav_register);
        const iconEl = document.getElementById('authPanelIcon');
        if (iconEl) iconEl.textContent = isForgot ? 'lock_reset' : (isLogin ? 'login' : 'person_add');
        const subEl = document.getElementById('authPanelSubtitle');
        if (subEl) {
            subEl.textContent = isForgot
                ? (S.password_forgot_hint || '')
                : (isLogin ? (S.auth_login_subtitle || '') : (S.auth_register_subtitle || ''));
        }
    }

    document.getElementById('btnShowForgot')?.addEventListener('click', () => showAuthView('forgot'));
    document.getElementById('btnBackToLogin')?.addEventListener('click', () => showAuthView('login'));

    document.getElementById('forgotForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await api('auth/forgot', {
                method: 'POST',
                body: JSON.stringify({ email: fd.get('email') }),
            });
            showToast(S.password_forgot_sent || 'Check your email', 'success');
            showAuthView('login');
            e.target.reset();
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await api('auth/login', {
                method: 'POST',
                body: JSON.stringify({ email: fd.get('email'), password: fd.get('password') }),
            });
            location.reload();
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        if (fd.get('password') !== fd.get('password_confirm')) {
            showToast(S.password_mismatch || 'Passwords do not match', 'error');
            return;
        }
        if (!fd.get('privacy_consent')) {
            await showAlert(S.privacy_consent || 'Please accept the privacy notice', { icon: 'shield', variant: 'warning' });
            return;
        }
        try {
            const data = await api('auth/register', {
                method: 'POST',
                body: JSON.stringify({
                    name: fd.get('name'),
                    email: fd.get('email'),
                    password: fd.get('password'),
                    role: fd.get('role'),
                }),
            });
            closeAllPanels();
            showToast(
                data.email_sent ? (S.register_success || 'Account created!') : (S.register_success_no_email || 'Welcome!'),
                'success'
            );
            setTimeout(() => location.reload(), 1600);
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    const doLogout = async () => {
        const ok = await showConfirm(S.confirm_logout || 'Log out?', {
            desc: S.confirm_logout_desc || '',
            icon: 'logout',
        });
        if (!ok) return;
        await api('auth/logout', { method: 'POST', body: '{}' });
        location.reload();
    };
    document.getElementById('btnLogout')?.addEventListener('click', doLogout);
    document.getElementById('btnLogoutMobile')?.addEventListener('click', doLogout);
    document.getElementById('btnLogoutMobileTop')?.addEventListener('click', doLogout);

    // ── Brand logo ↔ icon alternation ──
    function initBrandLogoRotation() {
        const logoImg = document.getElementById('brandLogoImg');
        const badge = document.getElementById('brandLogoBadge');
        const iconEl = document.getElementById('brandLogoIcon');
        const cycle = DS.brandLogoCycle || [];
        if (!logoImg || !badge || !iconEl || !cycle.length) return;

        let iconIdx = 0;
        let showingLogo = true;

        const applyIcon = (i, spin) => {
            const item = cycle[i];
            iconEl.textContent = item.icon;
            badge.style.setProperty('--brand-icon-color', item.color);
            if (spin) {
                badge.classList.remove('is-spinning');
                void badge.offsetWidth;
                badge.classList.add('is-spinning');
            }
        };

        const showLogo = () => {
            logoImg.classList.remove('is-hidden');
            badge.classList.remove('is-visible');
            badge.setAttribute('hidden', '');
            iconEl.classList.remove('fading');
            showingLogo = true;
        };

        const showIcon = (i, spin) => {
            applyIcon(i, spin);
            logoImg.classList.add('is-hidden');
            badge.removeAttribute('hidden');
            badge.classList.add('is-visible');
            showingLogo = false;
        };

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const startCycle = () => {
            if (badge.dataset.cycleStarted) return;
            badge.dataset.cycleStarted = '1';
            showLogo();

            setInterval(() => {
                if (showingLogo) {
                    if (reducedMotion) {
                        showIcon(iconIdx, false);
                        return;
                    }
                    logoImg.classList.add('is-hidden');
                    window.setTimeout(() => showIcon(iconIdx, true), 140);
                    return;
                }

                if (reducedMotion) {
                    showLogo();
                    iconIdx = (iconIdx + 1) % cycle.length;
                    return;
                }

                iconEl.classList.add('fading');
                window.setTimeout(() => {
                    showLogo();
                    iconIdx = (iconIdx + 1) % cycle.length;
                }, 220);
            }, reducedMotion ? 5000 : 3000);
        };

        window.addEventListener('dogeseeds-splash-done', startCycle, { once: true });
    }

    // ── Slogan rotation ──
    const MOBILE_LAYOUT = 768;

    function applySloganLayout() {
        const el = document.getElementById('sloganRotate');
        const scrollWrap = document.getElementById('mapHintScroll');
        const slogans = DS.slogans || [];
        if (!el || !scrollWrap) return;

        if (window.innerWidth > MOBILE_LAYOUT) {
            const probe = document.createElement('span');
            probe.className = 'map-hint-text';
            probe.style.cssText = 'position:absolute;visibility:hidden;white-space:nowrap;pointer-events:none;';
            const refStyle = window.getComputedStyle(el);
            probe.style.font = refStyle.font;
            probe.style.fontSize = refStyle.fontSize;
            probe.style.fontWeight = refStyle.fontWeight;
            document.body.appendChild(probe);

            let maxW = 0;
            slogans.forEach((s) => {
                probe.textContent = s;
                maxW = Math.max(maxW, probe.offsetWidth);
            });
            document.body.removeChild(probe);

            el.style.minWidth = `${maxW}px`;
            el.style.textAlign = 'center';
            scrollWrap.classList.remove('is-marquee');
            scrollWrap.style.removeProperty('--hint-marquee-offset');
            scrollWrap.style.removeProperty('--hint-marquee-duration');
            return;
        }

        el.style.minWidth = '';
        el.style.textAlign = '';

        requestAnimationFrame(() => {
            const overflow = el.scrollWidth - scrollWrap.clientWidth;
            if (overflow > 4) {
                scrollWrap.classList.add('is-marquee');
                scrollWrap.style.setProperty('--hint-marquee-offset', `-${overflow}px`);
                const duration = Math.max(10, Math.min(22, overflow / 18));
                scrollWrap.style.setProperty('--hint-marquee-duration', `${duration}s`);
            } else {
                scrollWrap.classList.remove('is-marquee');
                scrollWrap.style.removeProperty('--hint-marquee-offset');
                scrollWrap.style.removeProperty('--hint-marquee-duration');
            }
        });
    }

    function initSloganRotation() {
        const el = document.getElementById('sloganRotate');
        const iconEl = document.getElementById('mapHintIcon');
        const slogans = DS.slogans || [];
        const icons = DS.sloganIcons || [];
        if (!el) return;

        if (iconEl && icons[0]) iconEl.textContent = icons[0];
        applySloganLayout();
        window.addEventListener('resize', applySloganLayout);

        if (slogans.length < 2) return;

        let idx = 0;
        setInterval(() => {
            el.classList.add('fading');
            iconEl?.classList.add('fading');
            window.setTimeout(() => {
                idx = (idx + 1) % slogans.length;
                el.textContent = slogans[idx];
                if (iconEl && icons[idx]) iconEl.textContent = icons[idx];
                el.classList.remove('fading');
                iconEl?.classList.remove('fading');
                applySloganLayout();
            }, 280);
        }, 4500);
    }

    document.getElementById('copyWallet')?.addEventListener('click', () => {
        navigator.clipboard.writeText(document.getElementById('dogeWallet').textContent)
            .then(() => showToast(S.donate_copied || 'Copied!', 'success'));
    });

    // ── Post org map ──
    function initPickMap() {
        if (pickMap) {
            pickMap.invalidateSize();
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }

        const el = document.getElementById('pickMap');
        if (!el) return;

        const defaults = DS.mapDefaults;
        pickMap = L.map('pickMap').setView([defaults.lat, defaults.lng], defaults.zoom);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OSM',
            maxZoom: 19,
        }).addTo(pickMap);

        pickGeocoder = createGeocoder(pickMap, (center, geo) => {
            setPickLocation(center, geo?.name || geo?.properties?.display_name || '');
        });

        pickMap.on('click', (e) => setPickLocation(e.latlng));

        document.getElementById('btnPickMyLocation')?.addEventListener('click', () => {
            getUserLocation(false).then((loc) => {
                if (loc) setPickLocation(L.latLng(loc.lat, loc.lng));
            });
        });

        setTimeout(() => pickMap.invalidateSize(), 200);
        initSlideSubmit();
    }

    async function submitInquiryForm() {
        const form = document.getElementById('inquiryForm');
        if (!form) return;

        if (!form.checkValidity()) {
            form.reportValidity();
            if (resetInquirySlide) resetInquirySlide();
            return;
        }

        const fd = new FormData(form);
        const email = String(fd.get('email') || '').trim();
        const name = String(fd.get('name') || '').trim();
        const message = String(fd.get('message') || '').trim();

        if (!email && !name) {
            await showAlert(S.inquiry_hint || 'Include your contact details', { icon: 'contact_mail' });
            if (resetInquirySlide) resetInquirySlide();
            return;
        }

        try {
            const data = await api('listing-inquiry', {
                method: 'POST',
                body: JSON.stringify({
                    location_id: Number(form.dataset.locationId),
                    name,
                    email,
                    message,
                }),
            });
            form.reset();
            if (resetInquirySlide) resetInquirySlide();
            showToast(
                data.copy_sent ? (S.inquiry_sent_copy || S.inquiry_sent) : (S.inquiry_sent || 'Message sent!'),
                'success'
            );
        } catch (err) {
            showToast(err.message || S.inquiry_error || 'Could not send', 'error');
            if (resetInquirySlide) resetInquirySlide();
        }
    }

    // ── Slide to submit ──
    function bindSlideSubmit(wrap, onComplete, setReset) {
        if (!wrap) return;

        if (wrap._slideCleanup) {
            wrap._slideCleanup();
        }

        const thumb = wrap.querySelector('.thumb');
        if (!thumb) return;

        let max = wrap.clientWidth - thumb.offsetWidth - 8;
        let dragging = false;
        let startX = 0;
        let offsetX = 0;
        let done = false;

        function reset() {
            offsetX = 0;
            thumb.style.transform = 'translateX(0)';
            wrap.classList.remove('done');
            done = false;
        }

        if (typeof setReset === 'function') {
            setReset(reset);
        }

        function onMove(clientX) {
            if (!dragging || done) return;
            max = wrap.clientWidth - thumb.offsetWidth - 8;
            offsetX = Math.max(0, Math.min(max, clientX - startX));
            thumb.style.transform = `translateX(${offsetX}px)`;
            if (offsetX >= max - 4) {
                done = true;
                wrap.classList.add('done');
                onComplete();
            }
        }

        const onDown = (clientX) => {
            dragging = true;
            startX = clientX - offsetX;
        };

        const onMouseDown = (e) => onDown(e.clientX);
        const onTouchStart = (e) => onDown(e.touches[0].clientX);
        const onMouseMove = (e) => onMove(e.clientX);
        const onTouchMove = (e) => onMove(e.touches[0].clientX);
        const onEnd = () => {
            dragging = false;
            if (!done) reset();
        };

        thumb.addEventListener('mousedown', onMouseDown);
        thumb.addEventListener('touchstart', onTouchStart, { passive: true });
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('touchmove', onTouchMove, { passive: true });
        window.addEventListener('mouseup', onEnd);
        window.addEventListener('touchend', onEnd);

        wrap._slideCleanup = () => {
            thumb.removeEventListener('mousedown', onMouseDown);
            thumb.removeEventListener('touchstart', onTouchStart);
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('touchmove', onTouchMove);
            window.removeEventListener('mouseup', onEnd);
            window.removeEventListener('touchend', onEnd);
            reset();
        };
    }

    function initSlideSubmit() {
        const wrap = document.getElementById('slideSubmit');
        if (!wrap || wrap.dataset.bound) return;
        wrap.dataset.bound = '1';
        bindSlideSubmit(wrap, submitOrgForm, (fn) => { resetSlideSubmit = fn; });
    }

    async function submitOrgForm() {
        const form = document.getElementById('orgForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            document.getElementById('slideSubmit')?.classList.remove('done');
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }

        const fd = new FormData(form);
        const offers = [...form.querySelectorAll('input[name="offers[]"]:checked')].map(c => c.value);
        const needs = [...form.querySelectorAll('input[name="needs[]"]:checked')].map(c => c.value);

        if (!offers.length && !needs.length) {
            await showAlert(S.select_offers_hint || 'Select offers or needs', { icon: 'inventory_2' });
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }

        const orgType = form.querySelector('input[name="type"]:checked')?.value;
        if (orgType === 'donor' && !offers.length) {
            await showAlert(S.select_offers_hint || 'Select items to share', { icon: 'inventory_2' });
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }

        if (!fd.get('latitude') || !fd.get('longitude')) {
            await showAlert(S.map_hint || 'Set location on map', { icon: 'place' });
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }

        const pickupStart = fd.get('pickup_start');
        const pickupEnd = fd.get('pickup_end');
        if (!pickupStart || !pickupEnd) {
            await showAlert(S.pickup_invalid || 'Set pickup dates', { icon: 'schedule' });
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }
        if (new Date(String(pickupEnd)) <= new Date(String(pickupStart))) {
            await showAlert(S.pickup_end_before_start || 'End must be after start', { icon: 'schedule' });
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }

        const publishOk = await showConfirm(S.confirm_publish || 'Publish on map?', {
            desc: S.confirm_publish_desc || '',
            icon: 'add_location',
        });
        if (!publishOk) {
            if (resetSlideSubmit) resetSlideSubmit();
            return;
        }

        fd.delete('offers[]');
        fd.delete('needs[]');
        offers.forEach(v => fd.append('offers[]', v));
        needs.forEach(v => fd.append('needs[]', v));

        try {
            await api('organizations', {
                method: 'POST',
                body: fd,
            });
            form.reset();
            clearPhotoPreview();
            localStorage.removeItem('dogeseeds_post_prefs');
            if (pickMarker) { pickMap.removeLayer(pickMarker); pickMarker = null; }
            if (resetSlideSubmit) resetSlideSubmit();
            setDefaultPickupTimes();
            loadMapData();
            closeAllPanels();
            showToast(S.post_success || 'Published!', 'success');
        } catch (err) {
            showToast(err.message, 'error');
            if (resetSlideSubmit) resetSlideSubmit();
        }
    }

    document.getElementById('orgForm')?.addEventListener('submit', (e) => e.preventDefault());

    function clearPhotoPreview() {
        const input = document.getElementById('orgImage');
        const preview = document.getElementById('orgImagePreview');
        const previewImg = document.getElementById('orgImagePreviewImg');
        const uploadBtn = document.querySelector('.photo-upload-btn');
        if (input) input.value = '';
        if (previewImg) previewImg.src = '';
        preview?.classList.add('hidden');
        uploadBtn?.classList.remove('hidden');
    }

    // ── Onboarding wizard ──
    const ONBOARDING_KEY = 'dogeseeds_onboarding_done';
    const INTENT_KEY = 'dogeseeds_intent';

    const onboardingFlows = {
        seeker: ['welcome', 'seeker-categories', 'seeker-done'],
        sharer: ['welcome', 'sharer-type', 'sharer-intent', 'sharer-done'],
    };

    let onboardingPath = null;
    let onboardingStepIdx = 0;
    let onboardingState = { path: null, categories: [], orgType: null, offers: [], needs: [] };

    function setCategoryFilter(category) {
        currentCategory = category || '';
        document.querySelectorAll('.filter-pill').forEach(c => {
            c.classList.toggle('active', (c.dataset.category || '') === currentCategory);
        });
        loadMapData();
    }

    function orgTypeToRole(type) {
        if (['supermarket', 'grocery', 'restaurant', 'cafe', 'farmer', 'fisherman'].includes(type)) return 'business';
        if (['scout', 'volunteer'].includes(type)) return 'volunteer';
        if (type === 'ngo') return 'ngo';
        return 'user';
    }

    function prefillOrgForm(prefs) {
        const form = document.getElementById('orgForm');
        if (!form) return;
        if (prefs.type) {
            const radio = form.querySelector(`input[name="type"][value="${prefs.type}"]`);
            if (radio) radio.checked = true;
        }
        form.querySelectorAll('input[name="offers[]"]').forEach(cb => { cb.checked = false; });
        form.querySelectorAll('input[name="needs[]"]').forEach(cb => { cb.checked = false; });
        (prefs.offers || []).forEach(v => {
            const cb = form.querySelector(`input[name="offers[]"][value="${v}"]`);
            if (cb) cb.checked = true;
        });
        (prefs.needs || []).forEach(v => {
            const cb = form.querySelector(`input[name="needs[]"][value="${v}"]`);
            if (cb) cb.checked = true;
        });
    }

    function openRegisterWithRole(role) {
        openPanel('auth');
        document.querySelectorAll('.auth-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.auth === 'register');
            t.setAttribute('aria-selected', t.dataset.auth === 'register' ? 'true' : 'false');
        });
        document.getElementById('loginForm')?.classList.add('hidden');
        document.getElementById('registerForm')?.classList.remove('hidden');
        document.getElementById('authPanelTitle').textContent = S.nav_register;
        const iconEl = document.getElementById('authPanelIcon');
        if (iconEl) iconEl.textContent = 'person_add';
        const subEl = document.getElementById('authPanelSubtitle');
        if (subEl) subEl.textContent = S.auth_register_subtitle || '';
        const roleSelect = document.querySelector('#registerForm select[name="role"]');
        if (roleSelect && role) roleSelect.value = role;
    }

    function saveOnboardingPrefs() {
        if (document.getElementById('onboardingDismiss')?.checked) {
            localStorage.setItem(ONBOARDING_KEY, '1');
        }
        localStorage.setItem(INTENT_KEY, JSON.stringify(onboardingState));
    }

    function hideOnboarding() {
        const el = document.getElementById('onboardingWizard');
        if (!el) return;
        el.classList.remove('open');
        el.setAttribute('aria-hidden', 'true');
    }

    function showOnboardingStep(stepId) {
        document.querySelectorAll('.onboarding-step').forEach(s => {
            s.classList.toggle('hidden', s.dataset.step !== stepId);
        });

        const flow = onboardingFlows[onboardingPath] || ['welcome'];
        const titles = {
            welcome: [S.onboarding_welcome, S.onboarding_subtitle],
            'seeker-categories': [S.onboarding_seeker_cats, S.onboarding_seeker_cats_hint],
            'seeker-done': [S.onboarding_seeker_done, ''],
            'sharer-type': [S.onboarding_sharer_who, ''],
            'sharer-intent': [S.onboarding_sharer_intent, S.onboarding_sharer_intent_hint],
            'sharer-done': [S.onboarding_sharer_done, DS.canPost ? S.onboarding_sharer_done_user : S.onboarding_sharer_done_guest],
        };

        const [title, sub] = titles[stepId] || [S.onboarding_welcome, S.onboarding_subtitle];
        const titleEl = document.getElementById('onboardingTitle');
        const subEl = document.getElementById('onboardingSubtitle');
        if (titleEl) titleEl.textContent = title;
        if (subEl) {
            subEl.textContent = sub || '';
            subEl.classList.toggle('hidden', !sub);
        }

        const hintEl = document.getElementById('onboardingSharerDoneHint');
        if (hintEl && stepId === 'sharer-done') {
            hintEl.textContent = DS.canPost ? S.onboarding_sharer_done_user : S.onboarding_sharer_done_guest;
        }

        const postBtn = document.getElementById('onboardingBtnPost');
        const regBtn = document.getElementById('onboardingBtnRegister');
        if (postBtn && regBtn) {
            postBtn.classList.toggle('hidden', !DS.canPost);
            regBtn.classList.toggle('hidden', !!DS.canPost);
        }

        const progress = document.getElementById('onboardingProgress');
        if (progress) {
            progress.innerHTML = flow.map((s, i) =>
                `<span class="onboarding-dot${s === stepId ? ' active' : ''}"></span>`
            ).join('');
            progress.setAttribute('aria-hidden', flow.length < 2 ? 'true' : 'false');
        }

        const backBtn = document.getElementById('onboardingBack');
        const nextBtn = document.getElementById('onboardingNext');
        const isWelcome = stepId === 'welcome';
        const isDone = stepId === 'seeker-done' || stepId === 'sharer-done';

        backBtn?.classList.toggle('hidden', isWelcome || isDone);
        nextBtn?.classList.toggle('hidden', isWelcome || isDone);
        if (nextBtn && stepId === 'seeker-categories') nextBtn.textContent = S.onboarding_continue;
        if (nextBtn && stepId === 'sharer-type') nextBtn.textContent = S.onboarding_continue;
        if (nextBtn && stepId === 'sharer-intent') nextBtn.textContent = S.onboarding_continue;
    }

    function getCurrentOnboardingStep() {
        const flow = onboardingFlows[onboardingPath] || ['welcome'];
        return flow[onboardingStepIdx] || 'welcome';
    }

    function validateOnboardingStep(stepId) {
        if (stepId === 'seeker-categories') {
            const cats = [...document.querySelectorAll('input[name="wizard_seek_cat"]:checked')].map(c => c.value);
            if (!cats.length) {
                showToast(S.onboarding_select_category || 'Select a category', 'error');
                return false;
            }
            onboardingState.categories = cats;
            return true;
        }
        if (stepId === 'sharer-type') {
            const type = document.querySelector('input[name="wizard_org_type"]:checked')?.value;
            if (!type) {
                showToast(S.onboarding_select_org || 'Select who you are', 'error');
                return false;
            }
            onboardingState.orgType = type;
            return true;
        }
        if (stepId === 'sharer-intent') {
            const offerOn = document.getElementById('wizardIntentOffer')?.checked;
            const needOn = document.getElementById('wizardIntentNeed')?.checked;
            const offers = offerOn
                ? [...document.querySelectorAll('input[name="wizard_offer_cat"]:checked')].map(c => c.value)
                : [];
            const needs = needOn
                ? [...document.querySelectorAll('input[name="wizard_need_cat"]:checked')].map(c => c.value)
                : [];

            if (!offerOn && !needOn) {
                showToast(S.onboarding_select_intent || 'Select an option', 'error');
                return false;
            }
            if (offerOn && !offers.length) {
                showToast(S.onboarding_select_category || 'Select a category', 'error');
                return false;
            }
            if (needOn && !needs.length) {
                showToast(S.onboarding_select_category || 'Select a category', 'error');
                return false;
            }
            onboardingState.offers = offers;
            onboardingState.needs = needs;
            return true;
        }
        return true;
    }

    function finishSeekerFlow() {
        saveOnboardingPrefs();
        hideOnboarding();
        if (onboardingState.categories.length === 1) {
            setCategoryFilter(onboardingState.categories[0]);
        } else {
            setCategoryFilter('');
        }
        getUserLocation(true);
    }

    function finishSharerFlow(action) {
        saveOnboardingPrefs();
        hideOnboarding();

        const prefs = {
            type: onboardingState.orgType,
            offers: onboardingState.offers,
            needs: onboardingState.needs,
        };
        localStorage.setItem('dogeseeds_post_prefs', JSON.stringify(prefs));

        if (action === 'post' && DS.canPost) {
            prefillOrgForm(prefs);
            setNavActive('post');
            openPanel('post');
        } else         if (action === 'register') {
            localStorage.setItem('dogeseeds_pending_post', '1');
            openRegisterWithRole(orgTypeToRole(onboardingState.orgType || 'user'));
        }
    }

    function maybeOpenPendingPost() {
        if (localStorage.getItem('dogeseeds_pending_post') !== '1' || !DS.canPost) return;
        localStorage.removeItem('dogeseeds_pending_post');
        const raw = localStorage.getItem('dogeseeds_post_prefs');
        if (raw) {
            try { prefillOrgForm(JSON.parse(raw)); } catch (_) { /* ignore */ }
        }
        setNavActive('post');
        setTimeout(() => openPanel('post'), 500);
    }

    function initOnboarding() {
        const wizard = document.getElementById('onboardingWizard');
        if (!wizard) return;

        if (localStorage.getItem(ONBOARDING_KEY) === '1') {
            const saved = localStorage.getItem(INTENT_KEY);
            if (saved) {
                try { onboardingState = JSON.parse(saved); } catch (_) { /* ignore */ }
            }
            return;
        }

        document.querySelectorAll('.wizard-choice').forEach(btn => {
            btn.addEventListener('click', () => {
                onboardingPath = btn.dataset.path;
                onboardingState.path = onboardingPath;
                onboardingStepIdx = 1;
                showOnboardingStep(getCurrentOnboardingStep());
            });
        });

        document.getElementById('wizardIntentOffer')?.addEventListener('change', (e) => {
            document.getElementById('wizardOfferCats')?.classList.toggle('hidden', !e.target.checked);
        });
        document.getElementById('wizardIntentNeed')?.addEventListener('change', (e) => {
            document.getElementById('wizardNeedCats')?.classList.toggle('hidden', !e.target.checked);
        });

        document.getElementById('onboardingBack')?.addEventListener('click', () => {
            if (onboardingStepIdx <= 0) return;
            onboardingStepIdx -= 1;
            if (onboardingStepIdx === 0) onboardingPath = null;
            showOnboardingStep(getCurrentOnboardingStep());
        });

        document.getElementById('onboardingNext')?.addEventListener('click', () => {
            const stepId = getCurrentOnboardingStep();
            if (!validateOnboardingStep(stepId)) return;
            onboardingStepIdx += 1;
            showOnboardingStep(getCurrentOnboardingStep());
        });

        document.getElementById('onboardingSkip')?.addEventListener('click', () => {
            saveOnboardingPrefs();
            hideOnboarding();
        });

        document.getElementById('onboardingBtnPost')?.addEventListener('click', () => finishSharerFlow('post'));
        document.getElementById('onboardingBtnRegister')?.addEventListener('click', () => finishSharerFlow('register'));
        document.getElementById('onboardingBtnExplore')?.addEventListener('click', () => finishSharerFlow('explore'));

        const seekerDoneBtn = document.createElement('button');
        seekerDoneBtn.type = 'button';
        seekerDoneBtn.className = 'btn btn-block';
        seekerDoneBtn.id = 'onboardingSeekerExplore';
        seekerDoneBtn.textContent = S.onboarding_btn_explore || 'Explore the map';
        document.querySelector('[data-step="seeker-done"]')?.appendChild(seekerDoneBtn);
        seekerDoneBtn.addEventListener('click', finishSeekerFlow);

        onboardingPath = null;
        onboardingStepIdx = 0;
        showOnboardingStep('welcome');

        const openWizard = () => {
            setTimeout(() => {
                wizard.classList.add('open');
                wizard.setAttribute('aria-hidden', 'false');
            }, 450);
        };

        if (splashActive) {
            window.addEventListener('dogeseeds-splash-done', openWizard, { once: true });
        } else {
            openWizard();
        }
    }

    function initPhotoUpload() {
        const input = document.getElementById('orgImage');
        const preview = document.getElementById('orgImagePreview');
        const previewImg = document.getElementById('orgImagePreviewImg');
        const removeBtn = document.getElementById('orgImageRemove');
        const uploadBtn = document.querySelector('.photo-upload-btn');
        if (!input || input.dataset.bound) return;
        input.dataset.bound = '1';

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showToast(S.location_photo_too_large || 'Max 5 MB', 'error');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                preview.classList.remove('hidden');
                uploadBtn?.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });

        removeBtn?.addEventListener('click', () => clearPhotoPreview());
    }

    // ── Utils ──
    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function formatDate(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        return d.toLocaleString(DATE_LOCALE[DS.lang] || DATE_LOCALE.en, {
            month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
        });
    }

    document.getElementById('dsModalConfirm')?.addEventListener('click', () => closeModal(true));
    document.getElementById('dsModalCancel')?.addEventListener('click', () => closeModal(false));
    document.getElementById('dsModalBackdrop')?.addEventListener('click', (e) => {
        if (e.target.id === 'dsModalBackdrop') closeModal(false);
    });

    document.addEventListener('DOMContentLoaded', () => {
        initSplashLogoRotation();
        initMap();
        initBrandLogoRotation();
        initSloganRotation();
        initPhotoUpload();
        initOnboarding();
        maybeOpenPendingPost();
        setInterval(loadMapData, 60000);
    });
})();
