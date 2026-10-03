<?php

/**
 * Interactive map-based locator for verified craft galleries in Magelang.
 *
 * @var list<array<string, mixed>> $stores
 */

$title       = 'Lokator Galeri & Peta Pengrajin Magelang';
$current     = 'locator';
$bodyClass   = 'page-locator';
$stylesheets = ['assets/css/locator.css'];
$description = 'Peta kriya interaktif Magelang: temukan sanggar, bengkel pahat batu, dan sentra tenun di sekitar Borobudur dan Merapi.';

$mapCenter = ['lat' => -7.6075, 'lng' => 110.2038];
if ($stores !== []) {
    $lats = array_map(static fn ($s) => (float) $s['lat'], $stores);
    $lngs = array_map(static fn ($s) => (float) $s['lng'], $stores);
    $mapCenter = [
        'lat' => (float) array_sum($lats) / count($lats),
        'lng' => (float) array_sum($lngs) / count($lngs),
    ];
}

$storesJson = json_encode($stores, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$mapCenterJson = json_encode($mapCenter, JSON_UNESCAPED_SLASHES);

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
    'description' => $description,
]);

echo view('partials/masthead', ['current' => $current]);

?>
<main id="main">
    <section class="locator-hero">
        <div class="lok-shell">
            <div class="locator-hero__content">
                <h1>Peta Kriya Interaktif Magelang</h1>
                <p>Navigasi langsung menuju studio, bengkel pahat batu, dan sentra tenun di lingkar Borobudur &amp; Merapi.</p>
                <div class="locator-filters" data-locator-filters>
                    <form class="locator-search" data-locator-search>
                        <label for="locator-search-input">Cari sanggar, desa, atau jenis kriya</label>
                        <div class="locator-search__bar">
                            <input id="locator-search-input" type="search" name="q" placeholder="Cari desa wisata, nama sanggar, atau jenis kerajinan" autocomplete="off">
                            <button type="submit">Cari</button>
                        </div>
                    </form>
                    <div class="locator-filter-chips">
                        <button type="button" data-filter="open" data-active="true">Buka Sekarang</button>
                        <button type="button" data-filter="nearby">Terdekat Saya</button>
                        <button type="button" data-filter="workshop">Workshop Langsung</button>
                        <button type="button" data-filter="all" data-active="true">Semua Lokasi</button>
                    </div>
                </div>
                <div class="locator-stats">
                    <span><?= count($stores) ?> Sanggar Terverifikasi</span>
                    <span><?= count(array_filter($stores, static fn ($s) => $s['open_now'])) ?> Buka Hari Ini</span>
                </div>
            </div>
        </div>
    </section>

    <section class="locator-map-section">
        <div class="lok-shell locator-map-shell">
            <div class="locator-map-wrapper">
                <div id="locator-map" class="locator-map"></div>
                <button type="button" class="locator-my-location" data-my-location>Titik Saya</button>
            </div>
            <aside class="locator-sidebar">
                <header class="locator-sidebar__header">
                    <h2>Galeri Sentra Kriya</h2>
                    <p>Temukan sanggar terverifikasi dengan jam buka dan lokasi tepat.</p>
                </header>
                <div class="locator-list" data-locator-list></div>
            </aside>
        </div>
    </section>
</main>

<script>
    window.__LOCATOR_STORES__ = <?= $storesJson ?>;
    window.__LOCATOR_CENTER__ = <?= $mapCenterJson ?>;
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="<?= base_url('assets/js/locator.js') ?>"></script>

<?php

echo view('partials/document-end');
