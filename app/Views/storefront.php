<?php

use App\Services\Rupiah;

$title       = $store['name'] . ' — Storefront';
$current     = 'storefront';
$bodyClass   = 'page-storefront';
$stylesheets = ['assets/css/storefront.css'];

$hours      = $store['hours'];
$response   = $store['response'];
$documentary = $store['documentary'];
$facts      = array_filter([
    'Bahan Utama'        => $store['materials'],
    'Teknik & Finishing' => $store['craft'],
    'Asal Karya'         => $store['origins'],
], static fn (array $values): bool => $values !== []);

$facets = [];

foreach ($facts as $label => $values) {
    $labels = array_column($values, 'label');
    $used   = array_sum(array_column($values, 'count'));

    $facets[] = [
        'label'  => $label,
        'values' => implode(' · ', array_slice($labels, 0, 3)) . (count($labels) > 3 ? ' · +' . (count($labels) - 3) : ''),
        'used'   => 'Dipakai pada ' . $used . ' dari ' . $store['product_count'] . ' karya aktif',
    ];
}

$cityShort = trim(preg_replace('/^(Kabupaten|Kota)\s+/iu', '', (string) $store['city']) ?: $store['city']);
$hoursClass = match ($hours['state']) {
    'open'    => 'sf-intro__hours',
    'closed'  => 'sf-intro__hours sf-intro__hours--closed',
    'soon'    => 'sf-intro__hours sf-intro__hours--soon',
    default   => 'sf-intro__hours sf-intro__hours--unknown',
};

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
]);
?>

<?= view('partials/masthead', ['current' => $current]) ?>

<main class="lok-shell storefront">

    <nav class="breadcrumb" aria-label="Remah roti">
        <ol>
            <li><a href="<?= base_url() ?>">Beranda</a></li>
            <li><a href="<?= base_url('marketplace') ?>">Katalog</a></li>
            <li aria-current="page"><?= esc($store['name']) ?></li>
        </ol>
    </nav>

    <section class="sf-hero">
        <img class="sf-hero__cover" src="<?= base_url($store['cover']) ?>" alt="" width="640" height="420">
        <p class="sf-hero__eyebrow">Sentra Budaya <?= esc(preg_replace('/^Kecamatan\s+/iu', '', (string) $store['subdistrict']) ?: $cityShort) ?></p>
        <h1 class="sf-hero__title"><?= esc($store['name']) ?></h1>
        <?php if (! empty($store['tagline'])) : ?>
            <p class="sf-hero__tagline"><?= esc($store['tagline']) ?></p>
        <?php endif ?>
    </section>

    <section class="sf-intro" aria-label="Profil sentra">
        <p class="sf-intro__portrait">
            <?php if (! empty($store['logo_url'])) : ?>
                <img src="<?= base_url($store['logo_url']) ?>" alt="Logo <?= esc($store['name']) ?>" width="132" height="132">
            <?php else : ?>
                <span><?= esc(mb_substr($store['name'], 0, 2)) ?></span>
            <?php endif ?>
        </p>

        <div>
            <h2 class="sf-intro__name"><?= esc($store['name']) ?></h2>

            <div class="sf-intro__badges">
                <?php if ($store['verification_status'] === 'verified') : ?>
                    <span class="stock">Perajin Terverifikasi</span>
                <?php endif ?>
                <?php if (! empty($store['partner_level'])) : ?>
                    <span class="stock">Mitra <?= esc(ucfirst((string) $store['partner_level'])) ?></span>
                <?php endif ?>
            </div>

            <p class="sf-intro__where">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <span>
                    <?= esc(trim(($store['subdistrict'] ?? '') . ' ' . ($store['city'] ?? ''))) ?>
                    <?php if ($store['distance_km'] !== null) : ?>
                        (<?= esc(number_format($store['distance_km'], 1, ',', '.')) ?> km dari Candi Borobudur)
                    <?php endif ?>
                </span>
            </p>

            <?php if (! empty($store['description'])) : ?>
                <p class="sf-intro__story"><?= esc($store['description']) ?></p>
            <?php endif ?>

            <p class="<?= esc($hoursClass) ?>">
                <?php if ($hours['state'] === 'open') : ?>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                    </svg>
                <?php endif ?>
                <?= esc($hours['label']) ?>
                <?php if ($hours['hours'] !== '') : ?>
                    <span class="sf-intro__schedule"><?= esc($hours['days'] . ' · ' . $hours['hours']) ?></span>
                <?php endif ?>
            </p>
        </div>

        <div class="sf-stats">
            <div>
                <p class="sf-stats__value">
                    <?php if ($store['review_count'] > 0) : ?>
                        <?= esc(number_format($store['rating_average'], 1, ',', '.')) ?>
                        <small>/ 5,0</small>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                            <path d="M12 2.5l2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.35 6.19 20.4l1.11-6.47-4.7-4.58 6.5-.95L12 2.5z"/>
                        </svg>
                    <?php else : ?>
                        <small>Belum ada ulasan</small>
                    <?php endif ?>
                </p>
                <p class="sf-stats__label">
                    <?= $store['review_count'] > 0
                        ? $store['review_count'] . ' Ulasan Terkurasi'
                        : 'Belum ada ulasan terkurasi' ?>
                </p>
            </div>

            <div>
                <p class="sf-stats__value">
                    <?php if ($response['minutes'] !== null) : ?>
                        <?= esc($response['rate']) ?>%
                        <small> responded</small>
                    <?php else : ?>
                        <?= esc($store['sold_count']) ?>
                        <small>karya terjual</small>
                    <?php endif ?>
                </p>
                <p class="sf-stats__label">
                    <?php if ($response['minutes'] !== null) : ?>
                        Kecepatan Respon (<?= esc($response['minutes']) ?>m)
                    <?php else : ?>
                        <?= $store['product_count'] ?> karya di katalog
                    <?php endif ?>
                </p>
            </div>

            <?php if (! empty($store['phone'])) : ?>
                <p class="sf-stats__action">
                    <a class="btn btn--primary" href="tel:<?= esc(preg_replace('/[^\d+]/', '', (string) $store['phone']), 'attr') ?>">
                        Kontak Perajin
                    </a>
                </p>
            <?php endif ?>
        </div>
    </section>

    <section class="sf-story" aria-labelledby="sf-story-title">
        <figure class="sf-story__media">
            <?php if ($documentary !== null && ! empty($documentary['cover_image_url'])) : ?>
                <img src="<?= base_url($documentary['cover_image_url']) ?>"
                     alt="<?= esc($documentary['title']) ?>" loading="lazy">
            <?php else : ?>
                <img src="<?= base_url($store['cover']) ?>" alt="" loading="lazy">
            <?php endif ?>

            <?php if ($documentary !== null) : ?>
                <figcaption class="sf-story__caption">
                    <span class="sf-story__eyebrow">Dokumenter Perajin Mandiri</span>
                    <h3><?= esc($documentary['title']) ?></h3>

                    <?php if (! empty($documentary['excerpt'])) : ?>
                        <p><?= esc($documentary['excerpt']) ?></p>
                    <?php endif ?>

                    <?php if (! empty($documentary['media_url'])) : ?>
                        <a class="sf-story__play" href="<?= base_url($documentary['media_url']) ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
                                <path d="M8 5.5v13l11-6.5-11-6.5z"/>
                            </svg>
                            Tonton Proses Kriya
                        </a>
                    <?php endif ?>
                </figcaption>
            <?php endif ?>
        </figure>

        <div>
            <p class="sf-story__eyebrow">Jiwa di Balik Karya</p>
            <h2 class="sf-story__title" id="sf-story-title">Filosofi &amp; Sejarah Kriya <?= esc($cityShort) ?></h2>

            <?php if ($facets !== []) : ?>
                <dl class="sf-story__facts">
                    <?php foreach ($facets as $facet) : ?>
                        <div class="sf-story__fact">
                            <dt><?= esc($facet['label']) ?></dt>
                            <dd>
                                <?= esc($facet['values']) ?>
                                <small><?= esc($facet['used']) ?></small>
                            </dd>
                        </div>
                    <?php endforeach ?>
                </dl>
            <?php endif ?>
        </div>
    </section>

    <section class="sf-catalog" aria-labelledby="sf-catalog-title">
        <div class="sf-catalog__head">
            <div>
                <p class="eyebrow">Eksplorasi Karya</p>
                <h2 class="sf-catalog__title" id="sf-catalog-title">Katalog Kriya Terkurasi</h2>
            </div>
            <p class="sf-catalog__note">
                Menampilkan karya otentik bersertifikat asal <?= esc($cityShort) ?>, dikurasi langsung oleh Lokapren.
            </p>
        </div>

        <?php if ($categories !== []) : ?>
            <ul class="sf-chips">
                <li>
                    <a class="sf-chip<?= $category === '' ? ' is-active' : '' ?>"
                       href="<?= base_url('store/' . $store['slug']) ?>"
                       <?= $category === '' ? 'aria-current="page"' : '' ?>>
                        Semua Produk <span>(<?= esc($store['product_count']) ?>)</span>
                    </a>
                </li>

                <?php foreach ($categories as $option) : ?>
                    <li>
                        <a class="sf-chip<?= $category === $option['slug'] ? ' is-active' : '' ?>"
                           href="<?= base_url('store/' . $store['slug']) . '?' . http_build_query(['kategori' => $option['slug']]) ?>"
                           <?= $category === $option['slug'] ? 'aria-current="page"' : '' ?>>
                            <?= esc($option['name']) ?> <span>(<?= esc($option['product_count']) ?>)</span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <?php if ($products !== []) : ?>
            <div class="sf-catalog__grid">
                <?php foreach ($products as $product) : ?>
                    <?= view('storefront/card', ['product' => $product]) ?>
                <?php endforeach ?>
            </div>
        <?php else : ?>
            <p class="empty">Belum ada karya aktif di kategori ini.</p>
        <?php endif ?>
    </section>

    <section class="sf-assurance" aria-labelledby="sf-assurance-title">
        <div>
            <h2 class="sf-assurance__title" id="sf-assurance-title">Jaminan Keaslian Karya</h2>
            <p class="sf-assurance__text">
                Setiap pengiriman karya dipacking dengan standar museum dan disertai sertifikat bernomor seri resmi Lokapren.
            </p>
        </div>

        <dl class="sf-assurance__side">
            <div>
                <dt>Kayu &amp; Sertifikat Asal</dt>
                <dd>
                    <?= $facets !== [] ? esc($facets[0]['values']) : 'Bahan dicatat per karya' ?>
                    <?php if (! empty($store['store_code'])) : ?>
                        · Sertifikat <?= esc($store['store_code']) ?>
                    <?php endif ?>
                </dd>
            </div>
            <div>
                <dt>Alih daya ke pelanggan</dt>
                <dd>
                    <a href="<?= base_url('marketplace') . '?' . http_build_query(['store' => $store['slug']]) ?>">
                        Pelajari Kurasi Lokapren
                    </a>
                </dd>
            </div>
        </dl>

        <div>
            <button class="btn btn--primary" type="button" disabled
                    title="Halaman workshop hadir bersama Lokator Galeri &amp; Peta Pengrajin">
                Kunjungi Workshop
            </button>
            <p class="sf-assurance__text">Segera hadir bersama Lokator Galeri.</p>
        </div>
    </section>

</main>

<?= view('partials/footer') ?>

<?= view('partials/document-end') ?>