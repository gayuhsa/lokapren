<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo; Katalog
</p>

<div class="lp-section__head">
    <div>
        <h1>Katalog Kriya</h1>
        <p class="lp-muted">
            <?= esc((string) $result['total']) ?> karya dari
            <?= esc((string) ($result['seller_count'] ?? 0)) ?> sanggar.
        </p>
    </div>
</div>

<form class="lp-filter-bar" method="get" action="<?= route_to('catalog') ?>" data-autosubmit>
    <div class="lp-field">
        <label for="f-q">Kata kunci</label>
        <input id="f-q" type="search" name="q" value="<?= esc((string) $result['filters']['q']) ?>"
               placeholder="centik para, gebyok…">
    </div>

    <div class="lp-field">
        <label for="f-category">Kategori</label>
        <select id="f-category" name="category">
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= esc((string) $category['slug']) ?>"
                    <?= $result['filters']['category'] === $category['slug'] ? 'selected' : '' ?>>
                    <?= esc((string) $category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="lp-field">
        <label for="f-min">Harga minimum</label>
        <input id="f-min" type="number" name="min_price" min="0" step="1000"
               value="<?= esc((string) $result['filters']['min_price']) ?>" placeholder="0">
    </div>

    <div class="lp-field">
        <label for="f-max">Harga maksimum</label>
        <input id="f-max" type="number" name="max_price" min="0" step="1000"
               value="<?= esc((string) $result['filters']['max_price']) ?>" placeholder="Tak terbatas">
    </div>

    <div class="lp-field">
        <label for="f-sort">Urutkan</label>
        <select id="f-sort" name="sort">
            <?php
            $sorts = [
                'sold_count' => 'Paling laris',
                'newest'     => 'Terbaru',
                'price_asc'  => 'Harga termurah',
                'price_desc' => 'Harga termahal',
                'rating'     => 'Rating tertinggi',
                'name'       => 'Nama A-Z',
            ];
            ?>
            <?php foreach ($sorts as $value => $label): ?>
                <option value="<?= esc($value) ?>" <?= $result['sort'] === $value ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="lp-field">
        <button class="lp-btn lp-btn--solid" type="submit">Terapkan</button>
    </div>
</form>

<?php if ($result['products'] === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada karya yang cocok.</strong></p>
        <p class="lp-small">Coba kata kunci lain atau longgarkan filter harga.</p>
    </div>
<?php else: ?>
    <div class="lp-grid">
        <?php foreach ($result['products'] as $product): ?>
            <?= view('partials/product_card', ['product' => $product]) ?>
        <?php endforeach; ?>
    </div>

    <?php if ($result['pages'] > 1): ?>
        <nav class="lp-pagination" aria-label="Halaman katalog">
            <?php
            $query = array_filter([
                'q'         => $result['filters']['q'],
                'category'  => $result['filters']['category'],
                'seller'    => $result['filters']['seller'],
                'min_price' => $result['filters']['min_price'],
                'max_price' => $result['filters']['max_price'],
                'sort'      => $result['sort'],
            ], static fn ($value): bool => $value !== null && $value !== '');
            ?>

            <?php if ($result['has_prev']): ?>
                <a href="?<?= esc(http_build_query($query + ['page' => $result['page'] - 1])) ?>">Sebelumnya</a>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $result['pages']; $page++): ?>
                <?php if ($page === $result['page']): ?>
                    <span class="is-current" aria-current="page"><?= esc((string) $page) ?></span>
                <?php else: ?>
                    <a href="?<?= esc(http_build_query($query + ['page' => $page])) ?>"><?= esc((string) $page) ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($result['has_next']): ?>
                <a href="?<?= esc(http_build_query($query + ['page' => $result['page'] + 1])) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
