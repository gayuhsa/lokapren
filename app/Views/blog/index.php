<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo; Cerita
</p>

<div class="lp-section__head">
    <div>
        <h1>Cerita &amp; Panduan</h1>
        <p class="lp-muted">
            Catatan proses dari pengrajin, panduan untuk memulai usaha, dan cerita di balik karya.
        </p>
    </div>

    <?php if (! empty($categories)): ?>
        <span class="lp-pill"><?= esc((string) $total) ?> artikel</span>
    <?php endif; ?>
</div>

<?php if (! empty($categories)): ?>
    <nav class="lp-filter-bar" aria-label="Kategori artikel">
        <a href="<?= route_to('blog_index') ?>"
           <?= $category === '' ? 'aria-current="page"' : '' ?>>
            Semua
        </a>

        <?php foreach ($categories as $item): ?>
            <a href="<?= route_to('blog_index') ?>?category=<?= esc(rawurlencode((string) $item['slug'])) ?>"
               <?= $category === (string) $item['slug'] ? 'aria-current="page"' : '' ?>>
                <?= esc((string) $item['name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if ($posts === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada artikel di kategori ini.</strong></p>
        <p class="lp-small">
            Sementara pengrajin masih menulis, Anda bisa menjelajahi katalog dan membaca cerita di halaman toko
            masing-masing.
        </p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('catalog') ?>">Jelajahi katalog</a>
    </div>
<?php else: ?>
    <div class="lp-grid lp-grid--three">
        <?php foreach ($posts as $post): ?>
            <article class="lp-card lp-card--product">
                <a class="lp-card__media" href="<?= route_to('blog_show', (string) $post['id']) ?>">
                    <?php if (! empty($post['cover_path'])): ?>
                        <img src="<?= esc((string) url_gambar($post['cover_path'])) ?>"
                             alt="" loading="lazy" width="400" height="300">
                    <?php else: ?>
                        <span class="lp-card__placeholder" aria-hidden="true">Cerita</span>
                    <?php endif; ?>

                    <?php if (! empty($post['category_name'])): ?>
                        <span class="lp-tag lp-tag--stock"><?= esc((string) $post['category_name']) ?></span>
                    <?php endif; ?>
                </a>

                <div class="lp-card__body">
                    <?php if (! empty($post['shop_name'])): ?>
                        <?php if (! empty($post['shop_slug'])): ?>
                            <a class="lp-card__shop" href="<?= route_to('storefront', (string) $post['shop_slug']) ?>">
                                <?= esc((string) $post['shop_name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="lp-card__shop"><?= esc((string) $post['shop_name']) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <h2 class="lp-card__title">
                        <a href="<?= route_to('blog_show', (string) $post['id']) ?>">
                            <?= esc((string) $post['title']) ?>
                        </a>
                    </h2>

                    <?php if (! empty($post['excerpt'])): ?>
                        <p class="lp-card__text"><?= esc((string) $post['excerpt']) ?></p>
                    <?php endif; ?>

                    <div class="lp-card__foot">
                        <span class="lp-small lp-muted">
                            <?= format_tanggal($post['published_at']) ?>
                            <?php if ((int) ($post['view_count'] ?? 0) > 0): ?>
                                &middot; <?= esc((string) $post['view_count']) ?> dibaca
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="lp-pagination" aria-label="Halaman artikel">
            <?php if ($page > 1): ?>
                <a href="?<?= esc(http_build_query(array_filter([
                    'page'     => $page - 1,
                    'category' => $category,
                ]))) ?>">Sebelumnya</a>
            <?php endif; ?>

            <span class="is-current">Halaman <?= esc((string) $page) ?> dari <?= esc((string) $pages) ?></span>

            <?php if ($page < $pages): ?>
                <a href="?<?= esc(http_build_query(array_filter([
                    'page'     => $page + 1,
                    'category' => $category,
                ]))) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
