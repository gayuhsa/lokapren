<?php

use App\Services\Rupiah;

$title       = 'Katalog Kriya';
$current     = 'catalog';
$bodyClass   = 'page-catalog';
$stylesheets = ['assets/css/catalog.css'];

// The controller only keeps a category slug it has already confirmed exists,
// so looking the name up here cannot surface an unfiltered result set.
$categoryName = '';

foreach ($categories as $item) {
    if ($item['slug'] === $category) {
        $categoryName = (string) $item['name'];

        break;
    }
}

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
]);
?>

<?= view('partials/masthead', ['current' => $current]) ?>

<main class="lok-shell catalog">

    <nav class="breadcrumb" aria-label="Remah roti">
        <ol>
            <li><a href="<?= base_url() ?>">Beranda</a></li>
            <li><a href="<?= base_url('marketplace') ?>">Katalog</a></li>
            <?php if ($category !== '') : ?>
                <?php foreach ($categories as $option) : ?>
                    <?php if ($option['slug'] === $category) : ?>
                        <li aria-current="page"><?= esc($option['name']) ?></li>
                    <?php endif ?>
                <?php endforeach ?>
            <?php endif ?>
        </ol>
    </nav>

    <header class="catalog__head">
        <p class="eyebrow">Kriya Magelang</p>
        <h1>Katalog &amp; Lokasi</h1>
    </header>

    <form class="filters" method="get" action="<?= base_url('marketplace') ?>">
        <div class="filters__row">
            <label class="filters__search">
                <span class="filters__label">Cari karya</span>
                <input type="search" name="q" value="<?= esc($search) ?>"
                       placeholder="Misal: plakat, batik, anyaman" autocomplete="off">
            </label>

            <label class="filters__field">
                <span class="filters__label">Kategori</span>
                <select name="category">
                    <option value="">Semua kategori</option>
                    <?php foreach ($categories as $option) : ?>
                        <option value="<?= esc($option['slug'], 'attr') ?>"
                            <?= $option['slug'] === $category ? 'selected' : '' ?>>
                            <?= esc($option['name']) ?> (<?= (int) $option['product_count'] ?>)
                        </option>
                    <?php endforeach ?>
                </select>
            </label>

            <label class="filters__field">
                <span class="filters__label">Harga minimum</span>
                <input type="text" inputmode="numeric" name="min"
                       value="<?= $min === null ? '' : esc(Rupiah::number($min)) ?>"
                       placeholder="<?= esc(Rupiah::number($bounds['min'])) ?>">
            </label>

            <label class="filters__field">
                <span class="filters__label">Harga maksimum</span>
                <input type="text" inputmode="numeric" name="max"
                       value="<?= $max === null ? '' : esc(Rupiah::number($max)) ?>"
                       placeholder="<?= esc(Rupiah::number($bounds['max'])) ?>">
            </label>

            <label class="filters__field">
                <span class="filters__label">Urutkan</span>
                <select name="sort">
                    <?php foreach ($sortOptions as $key => $label) : ?>
                        <option value="<?= esc($key, 'attr') ?>" <?= $key === $sort ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </label>

            <button class="btn btn--primary" type="submit">Terapkan</button>

            <?php if ($hasFilters) : ?>
                <a class="btn btn--ghost" href="<?= base_url('marketplace') ?>">Reset</a>
            <?php endif ?>
        </div>
    </form>

    <?php if ($featured !== [] && ! $hasFilters) : ?>
        <section class="featured" aria-labelledby="featured-heading">
            <h2 class="featured__title" id="featured-heading">Karya Pilihan Pengrajin</h2>

            <div class="grid grid--featured">
                <?php foreach ($featured as $item) : ?>
                    <?= view('catalog/card', [
                        'product'   => $item,
                        'showStore' => true,
                        'heading'   => 3,
                    ]) ?>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>

    <section class="results" aria-labelledby="results-heading">
        <div class="results__head">
            <h2 id="results-heading">
                <?php if ($search !== '') : ?>
                    Hasil untuk &ldquo;<?= esc($search) ?>&rdquo;
                <?php elseif ($categoryName !== '') : ?>
                    Kategori: <?= esc($categoryName) ?>
                <?php elseif ($category !== '') : ?>
                    Kategori: <?= esc(str_replace('-', ' ', $category)) ?>
                <?php else : ?>
                    Semua Karya
                <?php endif ?>
            </h2>
            <p class="results__count"><?= $total ?> karya ditemukan</p>
        </div>

        <?php if ($products === []) : ?>
            <p class="empty">
                Belum ada karya yang cocok. Coba longgarkan filter atau kata kunci pencarian.
            </p>
        <?php else : ?>
            <div class="grid">
                <?php foreach ($products as $item) : ?>
                    <?= view('catalog/card', [
                        'product'   => $item,
                        'showStore' => true,
                        'heading'   => 3,
                    ]) ?>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if ($pages > 1) : ?>
            <nav class="pagination" aria-label="Navigasi halaman">
                <?php for ($i = 1; $i <= $pages; $i++) : ?>
                    <?php
                    $query = array_filter([
                        'q'        => $search,
                        'category' => $category,
                        'store'    => $store,
                        'min'      => $min === null ? null : Rupiah::number($min),
                        'max'      => $max === null ? null : Rupiah::number($max),
                        'sort'     => $sort,
                        'page'     => $i,
                    ], static fn ($v) => $v !== null && $v !== '');

                    $isCurrent = $i === $page;
                    ?>
                    <a class="pagination__item<?= $isCurrent ? ' is-current' : '' ?>"
                       href="<?= base_url('marketplace') . '?' . http_build_query($query) ?>"
                       <?= $isCurrent ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                <?php endfor ?>
            </nav>
        <?php endif ?>
    </section>

</main>

<?= view('partials/footer') ?>

<?= view('partials/document-end') ?>
