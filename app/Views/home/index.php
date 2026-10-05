<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="lp-hero">
    <div>
        <p class="lp-hero__kicker">Magelang · Pengrajin Lokal</p>
        <h1>Karya tangan pengrajin Magelang, langsung dari sanggarnya</h1>
        <p class="lp-hero__lead">
            Centik para, gebyok, wayang kulit, dan keramik yang dicetak tangan.
            Pesan langsung ke pengrajinnya, tanpa perantara panjang.
        </p>
        <div class="lp-hero__actions">
            <a class="lp-btn lp-btn--solid" href="<?= route_to('catalog') ?>">Jelajahi Katalog</a>
            <a class="lp-btn lp-btn--outline" href="<?= route_to('locator') ?>">Lihat Peta Pengrajin</a>
        </div>
    </div>

    <div class="lp-stats" aria-label="Sekilas Lokapren">
        <div>
            <strong><?= esc((string) ($stats['artisans'] ?? 0)) ?></strong>
            <span>Pengrajin</span>
        </div>
        <div>
            <strong><?= esc((string) ($stats['sellers'] ?? 0)) ?></strong>
            <span>Sanggar</span>
        </div>
        <div>
            <strong><?= esc((string) ($stats['products'] ?? 0)) ?></strong>
            <span>Karya Tayang</span>
        </div>
    </div>
</section>

<?php if ($featured !== []): ?>
    <section class="lp-section">
        <div class="lp-section__head">
            <div>
                <h2>Karya Pilihan</h2>
                <p>Yang paling dicari bulan ini.</p>
            </div>
            <a class="lp-more" href="<?= route_to('catalog') ?>">Lihat semua &rarr;</a>
        </div>

        <div class="lp-grid">
            <?php foreach ($featured as $product): ?>
                <?= view('partials/product_card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($sellers !== []): ?>
    <section class="lp-section">
        <div class="lp-section__head">
            <div>
                <h2>Sanggar Terverifikasi</h2>
                <p>Buka profil sanggar, lihat jam buka, lalu chat pengrajinnya.</p>
            </div>
            <a class="lp-more" href="<?= route_to('locator') ?>">Lihat peta &rarr;</a>
        </div>

        <div class="lp-grid lp-grid--three">
            <?php foreach ($sellers as $shop): ?>
                <article class="lp-card">
                    <div class="lp-card__body">
                        <?php if (! empty($shop['logo_path'])): ?>
                            <img src="<?= esc((string) url_gambar($shop['logo_path'])) ?>"
                                 alt="" width="56" height="56"
                                 style="border-radius:50%;object-fit:cover;">
                        <?php endif; ?>

                        <h3 class="lp-card__title">
                            <a href="<?= route_to('storefront', (string) $shop['slug']) ?>">
                                <?= esc((string) $shop['display_name']) ?>
                            </a>
                        </h3>

                        <?php if (! empty($shop['craft_focus'])): ?>
                            <p class="lp-card__text"><?= esc((string) $shop['craft_focus']) ?></p>
                        <?php endif; ?>

                        <?php if (! empty($shop['village'])): ?>
                            <p class="lp-card__text"><?= esc(lokasi_teks($shop)) ?></p>
                        <?php endif; ?>

                        <?= bintang($shop['rating_average'] ?? 0, $shop['rating_count'] ?? 0) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($categories !== []): ?>
    <section class="lp-section">
        <div class="lp-section__head">
            <div>
                <h2>Jelajahi Kategori</h2>
                <p>Setiap kategori dikerjakan oleh pengrajin yang fokus.</p>
            </div>
        </div>

        <div class="lp-row">
            <?php foreach ($categories as $category): ?>
                <a class="lp-chip"
                   href="<?= route_to('catalog') ?>?category=<?= esc((string) $category['slug']) ?>">
                    <?= esc((string) $category['name']) ?>
                    <?php if (! empty($category['product_count'])): ?>
                        <small>(<?= esc((string) $category['product_count']) ?>)</small>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($latest !== []): ?>
    <section class="lp-section">
        <div class="lp-section__head">
            <div>
                <h2>Baru Tayang</h2>
                <p>Karya yang baru dititipkan pengrajin minggu ini.</p>
            </div>
            <a class="lp-more" href="<?= route_to('catalog') ?>?sort=newest">Lihat semua &rarr;</a>
        </div>

        <div class="lp-grid">
            <?php foreach ($latest as $product): ?>
                <?= view('partials/product_card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($posts !== []): ?>
    <section class="lp-section">
        <div class="lp-section__head">
            <div>
                <h2>Cerita dari Sanggar</h2>
                <p>Proses, technique, dan alasan di balik setiap karya.</p>
            </div>
            <a class="lp-more" href="<?= route_to('blog_index') ?>">Baca semua &rarr;</a>
        </div>

        <div class="lp-grid lp-grid--three">
            <?php foreach ($posts as $post): ?>
                <article class="lp-card">
                    <div class="lp-card__body">
                        <h3 class="lp-card__title">
                            <a href="<?= route_to('blog_show', (int) $post['id']) ?>">
                                <?= esc((string) $post['title']) ?>
                            </a>
                        </h3>
                        <?php if (! empty($post['excerpt'])): ?>
                            <p class="lp-card__text"><?= esc((string) $post['excerpt']) ?></p>
                        <?php endif; ?>
                        <small class="lp-muted"><?= esc(format_tanggal($post['published_at'])) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?= $this->endSection() ?>
