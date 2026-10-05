/* Lokapren — progressive enhancement only.
 *
 * Every interaction on this site works without JavaScript: forms post, links
 * navigate, the locator falls back to a plain list of sanggar, and checkout
 * renders the total that the server already calculated. This file adds small
 * conveniences on top — quantity steppers, quick replies, the Leaflet map, and
 * a live shipping estimate.
 */
(function () {
    'use strict';

    /* Quantity steppers in the cart.
     *
     * The input stays a real <input type="number"> inside a real <form>, so the
     * value is submitted normally; the buttons only nudge it.
     */
    function initQuantitySteppers() {
        document.querySelectorAll('[data-qty-stepper]').forEach(function (wrap) {
            var input = wrap.querySelector('input');
            var minus = wrap.querySelector('[data-step="-1"]');
            var plus  = wrap.querySelector('[data-step="1"]');

            if (!input) {
                return;
            }

            function nudge(step) {
                var min = parseInt(input.getAttribute('min') || '1', 10);
                var max = parseInt(input.getAttribute('max') || '999', 10);
                var next = (parseInt(input.value || String(min), 10) || min) + step;

                input.value = String(Math.min(Math.max(next, min), max));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (minus) { minus.addEventListener('click', function () { nudge(-1); }); }
            if (plus)  { plus.addEventListener('click', function () { nudge(1);  }); }
        });
    }

    /* Show how long is left in the review textarea. */
    function initTextCounters() {
        document.querySelectorAll('[data-counter]').forEach(function (field) {
            var output = document.getElementById(field.getAttribute('data-counter'));

            if (!output) {
                return;
            }

            var max = parseInt(field.getAttribute('maxlength') || '0', 10);

            function update() {
                var length = field.value.length;
                output.textContent = max > 0 ? length + ' / ' + max : String(length);
            }

            field.addEventListener('input', update);
            update();
        });
    }

    /* Confirm before a destructive POST (deleting a product or a story).
     *
     * These are real form submissions, so `confirm()` here is a safety net
     * rather than the mechanism.
     */
    function initConfirmations() {
        document.querySelectorAll('[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });
    }

    /* Auto-submit the catalog filter when a select changes, so the page does not
     * need an explicit "Terapkan" click on desktop. */
    function initAutoSubmit() {
        document.querySelectorAll('[data-autosubmit]').forEach(function (form) {
            form.querySelectorAll('select, input[type="radio"]').forEach(function (field) {
                field.addEventListener('change', function () { form.submit(); });
            });
        });
    }

    /* Seller quick replies fill the chat composer.
     *
     * The composer is a normal form, so this only types into it; the message
     * still has to be sent explicitly.
     */
    function initQuickReplies() {
        document.querySelectorAll('[data-quick-reply]').forEach(function (button) {
            button.addEventListener('click', function () {
                var composer = document.querySelector('[data-chat-input]');

                if (!composer) {
                    return;
                }

                composer.value = button.getAttribute('data-quick-reply');
                composer.focus();
            });
        });
    }

    /* Shipping estimate on the checkout page.
     *
     * The server already priced the order and the figure on screen comes from
     * it; this only re-reads the per-option total the view rendered when the
     * buyer picks a different courier.
     */
    function initShippingRecalc() {
        var output = document.querySelector('[data-grand-total] span:last-child');
        var options = document.querySelectorAll('[data-total]');

        if (!output || options.length === 0) {
            return;
        }

        options.forEach(function (option) {
            option.addEventListener('change', function () {
                if (option.checked) {
                    output.textContent = option.getAttribute('data-total');
                }
            });
        });
    }

    /* The store locator map.
     *
     * Markers arrive as JSON in a data attribute, which keeps the map working
     * without a server-rendered inline <script>. If Leaflet is unavailable the
     * plain list beside the map is still complete, so nothing is hidden behind
     * this block.
     */
    function initLocatorMap() {
        var node = document.getElementById('lp-map');

        if (!node || typeof window.L === 'undefined') {
            return;
        }

        var markers;

        try {
            markers = JSON.parse(node.getAttribute('data-markers') || '[]');
        } catch (error) {
            return;
        }

        if (!Array.isArray(markers) || markers.length === 0) {
            return;
        }

        var map = window.L.map(node, { scrollWheelZoom: false });

        window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var group = window.L.featureGroup();

        markers.forEach(function (marker) {
            if (typeof marker.lat !== 'number' || typeof marker.lon !== 'number') {
                return;
            }

            var text = '<strong>' + escapeHtml(marker.name) + '</strong>';

            if (marker.owner) {
                text += '<br>' + escapeHtml(marker.owner);
            }

            if (marker.place) {
                text += '<br>' + escapeHtml(marker.place);
            }

            if (marker.craft) {
                text += '<br><em>' + escapeHtml(marker.craft) + '</em>';
            }

            if (marker.verified) {
                text += '<br><small>Terverifikasi</small>';
            }

            if (marker.url) {
                text += '<br><a href="' + escapeHtml(marker.url) + '">Lihat toko</a>';
            }

            group.addLayer(
                window.L.marker([marker.lat, marker.lon])
                    .bindPopup(text)
                    .addTo(map)
            );
        });

        if (group.getLayers().length > 0) {
            map.fitBounds(group.getBounds().pad(0.2));
        }
    }

    /* Popup content is assembled from strings above, so any value coming from a
     * seller's own profile is escaped before it reaches innerHTML. */
    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        initQuantitySteppers();
        initTextCounters();
        initConfirmations();
        initAutoSubmit();
        initQuickReplies();
        initShippingRecalc();
        initLocatorMap();
    });
})();
