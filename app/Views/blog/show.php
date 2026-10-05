<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo;
    <a href="<?= route_to('blog_index') ?>">Cerita</a>
    <?php if (! empty($post['category_slug'])): ?>
        &rsaquo; <?= esc((string) $post['category_name']) ?>
    <?php endif; ?>
</p>

<article class="lp-article">

    <header class="lp-article__head">
        <?php if (! empty($post['category_name'])): ?>
            <span class="lp-pill">
                <a href="<?= route_to('blog_index') ?>?category=<?= esc(rawurlencode((string) $post['category_slug'])) ?>">
                    <?= esc((string) $post['category_name']) ?>
                </a>
            </span>
        <?php endif; ?>

        <h1><?= esc((string) $post['title']) ?></h1>

        <?php if (! empty($post['excerpt'])): ?>
            <p class="lp-lead"><?= esc((string) $post['excerpt']) ?></p>
        <?php endif; ?>

        <div class="lp-row lp-article__meta">
            <?php if (! empty($post['shop_name'])): ?>
                <?php if (! empty($post['shop_slug'])): ?>
                    <a class="lp-chip" href="<?= route_to('storefront', (string) $post['shop_slug']) ?>">
                        <?php if (! empty($post['shop_logo'])): ?>
                            <img class="lp-chip__logo"
                                 src="<?= esc((string) url_gambar($post['shop_logo'])) ?>"
                                 alt="" width="24" height="24" loading="lazy">
                        <?php endif; ?>
                        <?= esc((string) $post['shop_name']) ?>
                    </a>
                <?php else: ?>
                    <span class="lp-chip"><?= esc((string) $post['shop_name']) ?></span>
                <?php endif; ?>
            <?php elseif (! empty($post['author_username'])): ?>
                <span class="lp-chip">Tim Lokapren</span>
            <?php endif; ?>

            <span class="lp-small lp-muted">
                <?php if (! empty($post['published_at'])): ?>
                    <?= format_tanggal($post['published_at']) ?>
                    <?php if (! empty($post['author_username'])): ?>
                        &middot; oleh <?= esc((string) $post['author_username']) ?>
                    <?php endif; ?>
                <?php endif; ?>
            </span>

            <?php if ((int) ($post['view_count'] ?? 0) > 0): ?>
                <span class="lp-small lp-muted">
                    <?= esc((string) $post['view_count']) ?> dibaca
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if (! empty($post['cover_path'])): ?>
        <figure class="lp-article__cover">
            <img src="<?= esc((string) url_gambar($post['cover_path'])) ?>"
                 alt="" width="1200" height="675">
        </figure>
    <?php endif; ?>

    <?php
    // The body is stored as authored plain text with blank-line paragraphs, so
    // it is rendered as paragraphs rather than trusted HTML.
    $paragraphs = array_values(array_filter(
        array_map('trim', preg_split('/\R{2,}/', (string) ($post['body'] ?? '')) ?: []),
        static fn (string $line): bool => $line !== ''
    ));
    ?>

    <?php if ($paragraphs !== []): ?>
        <div class="lp-prose">
            <?php foreach ($paragraphs as $paragraph): ?>
                <p><?= nl2br(esc($paragraph)) ?></p>
            <?php endforeach; ?>
        </div>
    <?php elseif (! empty($post['excerpt'])): ?>
        <p class="lp-muted"><?= esc((string) $post['excerpt']) ?></p>
    <?php else: ?>
        <p class="lp-muted">Artikel ini belum memiliki isi.</p>
    <?php endif; ?>

    <?php if (! empty($post['shop_slug'])): ?>
        <aside class="lp-panel lp-article__cta">
            <div>
                <h2>Mau lihat karya dari sanggar ini?</h2>
                <p class="lp-muted">
                    Kunjungi halaman toko <?= esc((string) $post['shop_name']) ?> untuk melihat produk
                    yang tersedia dan cara mengetahuinya.
                </p>
            </div>
            <a class="lp-btn lp-btn--solid" href="<?= route_to('storefront', (string) $post['shop_slug']) ?>">
                Buka toko
            </a>
        </aside>
    <?php endif; ?>
</article>

<?php if (! empty($related)): ?>
    <section class="lp-section">
        <div class="lp-section__head">
            <h2>Bacaan lain</h2>
            <a class="lp-icon-link" href="<?= route_to('blog_index') ?>">Semua artikel</a>
        </div>

        <div class="lp-grid lp-grid--three">
            <?php foreach ($related as $item): ?>
                <article class="lp-card lp-card--product">
                    <a class="lp-card__media" href="<?= route_to('blog_show', (string) $item['id']) ?>">
                        <?php if (! empty($item['cover_path'])): ?>
                            <img src="<?= esc((string) url_gambar($item['cover_path'])) ?>"
                                 alt="" loading="lazy" width="400" height="300">
                        <?php else: ?>
                            <span class="lp-card__placeholder" aria-hidden="true">Cerita</span>
                        <?php endif; ?>
                    </a>

                    <div class="lp-card__body">
                        <?php if (! empty($item['category_name'])): ?>
                            <span class="lp-card__shop"><?= esc((string) $item['category_name']) ?></span>
                        <?php endif; ?>

                        <h3 class="lp-card__title">
                            <a href="<?= route_to('blog_show', (string) $item['id']) ?>">
                                <?= esc((string) $item['title']) ?>
                            </a>
                        </h3>

                        <?php if (! empty($item['excerpt'])): ?>
                            <p class="lp-card__text"><?= esc((string) $item['excerpt']) ?></p>
                        <?php endif; ?>

                        <?php if (! empty($item['published_at'])): ?>
                            <span class="lp-small lp-muted"><?= format_tanggal($item['published_at']) ?></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?= $this->endSection() ?>
