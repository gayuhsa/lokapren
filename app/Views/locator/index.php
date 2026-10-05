<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo; Lokasi Pengrajin
</p>

<div class="lp-section__head">
    <div>
        <h1>Lokasi Pengrajin</h1>
        <p class="lp-muted">
            Peta pengrajin di Magelang dan sekitarnya. Sumber peta: OpenStreetMap.
        </p>
    </div>
    <span class="lp-pill"><?= esc((string) count($markers)) ?> sanggar</span>
</div>

<form class="lp-search" method="get" action="<?= route_to('locator') ?>" style="margin-bottom:1rem;">
    <label class="lp-visually-hidden" for="q">Cari sanggar</label>
    <input id="q" type="search" name="q" value="<?= esc($query) ?>"
           placeholder="Cari nama sanggar, jenis kerajinan, atau desa…">
    <button class="lp-btn lp-btn--solid" type="submit">Cari</button>
</form>

<?php if ($markers === []): ?>
    <div class="lp-empty">
        <p><strong>Tidak ada sanggar yang cocok.</strong></p>
        <p class="lp-small">
            Hanya sanggar yang sudah mengisi titik peta (latitude dan longitude) yang muncul di sini.
        </p>
        <a class="lp-btn lp-btn--outline" href="<?= route_to('locator') ?>">Tampilkan semua</a>
    </div>
<?php else: ?>
    <div class="lp-split">
        <div>
            <div class="lp-map" id="lp-map"
                 data-markers="<?= esc(json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">
                <noscript>
                    <p class="lp-map-note">Peta membutuhkan JavaScript. Daftar sanggar ada di sebelah kanan.</p>
                </noscript>
            </div>

            <p class="lp-map-note">
                Titik peta berasal dari lokasi yang diisi pengrajin sendiri, bukan hasil survei.
            </p>
        </div>

        <aside class="lp-aside-sticky">
            <div class="lp-panel" style="max-height:60vh;overflow:auto;">
                <h2>Daftar sanggar</h2>

                <ul class="lp-stack" style="list-style:none;padding:0;margin:0;">
                    <?php foreach ($pins as $pin): ?>
                        <li style="border-bottom:1px solid var(--lp-line);padding-bottom:.75rem;">
                            <a href="<?= route_to('storefront', (string) $pin['slug']) ?>">
                                <strong><?= esc((string) $pin['display_name']) ?></strong>
                            </a>
                            <?php if ((int) $pin['rating_count'] > 0): ?>
                                <?= bintang($pin['rating_average'], $pin['rating_count']) ?>
                            <?php endif; ?>

                            <p class="lp-small lp-muted">
                                <?php
                                $place = implode(', ', array_filter([
                                    $pin['village'] ?? null,
                                    $pin['district'] ?? null,
                                    $pin['regency'] ?? null,
                                ], static fn ($part): bool => $part !== null && $part !== ''));
                                ?>
                                <?= $place !== '' ? esc($place) : esc((string) ($pin['craft_focus'] ?? '')) ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin="" defer></script>
<?php endif; ?>

<?= $this->endSection() ?>
