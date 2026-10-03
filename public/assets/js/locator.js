(function () {
    var stores = window.__LOCATOR_STORES__ || [];
    var center = window.__LOCATOR_CENTER__ || {lat: -7.6075, lng: 110.2038};

    var mapEl = document.getElementById('locator-map');
    if (!mapEl) {
        return;
    }

    var map = L.map('locator-map').setView([center.lat, center.lng], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var markers = [];
    var listEl = document.querySelector('[data-locator-list]');
    var searchForm = document.querySelector('[data-locator-search]');
    var searchInput = document.getElementById('locator-search-input');
    var filterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-locator-filters] button[data-filter]'));
    var myLocationBtn = document.querySelector('[data-my-location]');

    var activeFilter = {open: true, nearby: false, workshop: false, all: true};

    function buildMarkerHtml(store) {
        var openText = store.open_now ? 'Buka sekarang' : 'Saat ini tutup';
        return '<div><strong>' + escapeHtml(store.name) + '</strong><br><span>' + escapeHtml(store.hours_label) + '</span><br><a href="' + store.storefront_url + '">Lihat profil sanggar</a></div>';
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderList(filtered) {
        if (!listEl) return;
        listEl.innerHTML = '';
        if (filtered.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'locator-card';
            empty.innerHTML = '<div class="locator-card__name">Tidak ada sanggar sesuai filter</div><div class="locator-card__meta">Coba ubah kata kunci atau filter.</div>';
            listEl.appendChild(empty);
            return;
        }
        filtered.forEach(function (store) {
            var card = document.createElement('button');
            card.type = 'button';
            card.className = 'locator-card';
            card.dataset.id = store.id;
            card.innerHTML = '<div class="locator-card__name">' + escapeHtml(store.name) + '</div>' +
                '<div class="locator-card__meta">' + [store.subdistrict, store.city].filter(Boolean).map(escapeHtml).join(', ') + '</div>' +
                '<div class="locator-card__status ' + (store.open_now ? 'locator-card__status--open' : 'locator-card__status--closed') + '">' +
                '<span>' + escapeHtml(store.hours_label) + '</span>' +
                '<span>' + escapeHtml(store.hours_range || '') + '</span>' +
                '</div>';
            card.addEventListener('click', function () {
                markers.forEach(function (m) {
                    if (m.store && m.store.id === store.id) {
                        map.setView([m.store.lat, m.store.lng], 14);
                        m.marker.openPopup();
                    }
                });
                document.querySelectorAll('.locator-card').forEach(function (c) { c.classList.remove('is-active'); });
                card.classList.add('is-active');
            });
            listEl.appendChild(card);
        });
    }

    function applyFilters() {
        var q = (searchInput && searchInput.value || '').toLowerCase();
        var filtered = stores.filter(function (store) {
            if (activeFilter.open && !store.open_now) {
                return false;
            }
            if (q) {
                var hay = [store.name, store.subdistrict, store.city, store.address_line].filter(Boolean).join(' ').toLowerCase();
                if (hay.indexOf(q) === -1) {
                    return false;
                }
            }
            return true;
        });
        renderList(filtered);
        markers.forEach(function (m) {
            var show = filtered.some(function (s) { return s.id === m.store.id; });
            if (show) {
                m.marker.addTo(map);
            } else {
                map.removeLayer(m.marker);
            }
        });
    }

    if (stores.length > 0) {
        stores.forEach(function (store) {
            var marker = L.marker([store.lat, store.lng]).addTo(map);
            marker.bindPopup(buildMarkerHtml(store));
            markers.push({store: store, marker: marker});
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            applyFilters();
        });
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                applyFilters();
            });
        }
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-filter');
            filterButtons.forEach(function (b) {
                b.setAttribute('data-active', 'false');
            });
            btn.setAttribute('data-active', 'true');
            if (key === 'all') {
                activeFilter.open = false;
                activeFilter.nearby = false;
            } else if (key === 'open') {
                activeFilter.open = !activeFilter.open;
                btn.setAttribute('data-active', activeFilter.open ? 'true' : 'false');
            } else if (key === 'nearby') {
                activeFilter.nearby = !activeFilter.nearby;
                btn.setAttribute('data-active', activeFilter.nearby ? 'true' : 'false');
            } else if (key === 'workshop') {
                activeFilter.workshop = !activeFilter.workshop;
                btn.setAttribute('data-active', activeFilter.workshop ? 'true' : 'false');
            }
            applyFilters();
        });
    });

    if (myLocationBtn) {
        myLocationBtn.addEventListener('click', function () {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    map.setView([pos.coords.latitude, pos.coords.longitude], 13);
                    L.circleMarker([pos.coords.latitude, pos.coords.longitude], {
                        radius: 6,
                        color: '#2563eb',
                        fillColor: '#60a5fa',
                        fillOpacity: 0.9
                    }).addTo(map).bindPopup('Titik Anda');
                });
            }
        });
    }

    applyFilters();
})();
