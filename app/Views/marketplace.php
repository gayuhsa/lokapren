<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplace</title>
    <style>
        body { font-family: sans-serif; max-width: 60em; margin: 3rem auto; padding: 0 1rem; }
        form.logout { text-align: right; }
        button.logout { font: inherit; padding: .5rem 1rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button.logout:hover { background: #b91c1c; }
        .top-actions { display: flex; justify-content: flex-end; align-items: center; gap: .5rem; }
        a.settings-link { font: inherit; padding: .5rem 1rem; background: #2563eb; color: #fff; border-radius: 4px; text-decoration: none; }
        a.settings-link:hover { background: #1d4ed8; }
        .notice-temp { background: #e0f2fe; border: 1px solid #38bdf8; color: #075985; padding: .5rem; border-radius: 4px; }
        .layout { display: flex; gap: 2rem; margin-top: 1rem; align-items: flex-start; }
        .sidebar { flex: 0 0 12em; }
        .sidebar h2 { font-size: 1rem; margin: 0 0 .5rem; }
        .sidebar ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .25rem; }
        .sidebar a { display: block; padding: .4rem .6rem; text-decoration: none; color: #333; border-radius: 4px; }
        .sidebar a:hover { background: #eef2ff; }
        .sidebar a.active { background: #2563eb; color: #fff; }
        .content { flex: 1; }
        .empty { text-align: center; color: #666; margin-top: 3rem; }

        .filters { border: 1px solid #ddd; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-end; }
        .filters label { font-weight: bold; font-size: .85rem; display: block; margin-bottom: .25rem; }
        .filters select { font: inherit; padding: .4rem .5rem; border: 1px solid #aaa; border-radius: 4px; }
        .range-row { display: flex; gap: 1rem; }
        .range-field { display: flex; flex-direction: column; }
        .range-field input { min-width: 9em; font: inherit; padding: .4rem .5rem; border: 1px solid #aaa; border-radius: 4px; }

        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .card { border: 1px solid #ddd; border-radius: 6px; overflow: hidden; display: block; text-decoration: none; color: inherit; }
        .card:hover { border-color: #2563eb; box-shadow: 0 2px 8px rgba(0, 0, 0, .1); }
        .card .thumb { aspect-ratio: 1 / 1; }
        .card .thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .card .meta { padding: .5rem; }
        .card .name { font-weight: 600; }
        .card .price { color: #2563eb; font-weight: bold; margin-top: .25rem; }
        .card .rating { color: #a16207; font-size: .85rem; margin-top: .25rem; }
    </style>
</head>
<body>
    <p class="notice-temp"><strong>Temporary placeholder frontend.</strong> This page will be replaced by a proper design.</p>
    <div class="top-actions">
        <a class="settings-link" href="<?= esc(site_url('settings')) ?>">Pengaturan</a>
        <form class="logout" action="<?= url_to('logout') ?>" method="get">
            <button type="submit" class="logout">Log out</button>
        </form>
    </div>

    <?php
    $categories = [
        'food'    => 'Makanan',
        'fashion' => 'Fashion',
        'beauty'  => 'Kecantikan',
        'home'    => 'Rumah Tangga',
    ];

    $qs = http_build_query(array_filter([
        'sort' => $sort !== '' ? $sort : null,
        'min'  => $min !== '' ? $min : null,
        'max'  => $max !== '' ? $max : null,
    ]));
    $suffix = $qs !== '' ? '&' . $qs : '';
    $allUrl = site_url('marketplace') . ($qs !== '' ? '?' . $qs : '');

    $minVal = $min !== '' ? (int) $min : $scaleMin;
    $maxVal = $max !== '' ? (int) $max : $scaleMax;
    ?>

    <div class="layout">
        <aside class="sidebar">
            <h2>Kategori</h2>
            <ul>
                <li>
                    <a href="<?= esc($allUrl) ?>"<?= $category === '' ? ' class="active"' : '' ?>>Semua Produk</a>
                </li>
                <?php foreach ($categories as $categoryKey => $categoryLabel) : ?>
                    <li>
                        <a href="<?= esc(site_url('marketplace') . '?category=' . urlencode($categoryKey) . $suffix) ?>"<?= $category === $categoryKey ? ' class="active"' : '' ?>><?= esc($categoryLabel) ?></a>
                    </li>
                <?php endforeach ?>
            </ul>
        </aside>

        <main class="content">
            <form class="filters" action="<?= esc(site_url('marketplace')) ?>" method="get">
                <input type="hidden" name="category" value="<?= esc($category) ?>">

                <div>
                    <label for="sort">Urutkan</label>
                    <select id="sort" name="sort">
                        <option value=""<?= $sort === '' ? ' selected' : '' ?>>Relevansi</option>
                        <option value="price_asc"<?= $sort === 'price_asc' ? ' selected' : '' ?>>Harga Terendah</option>
                        <option value="price_desc"<?= $sort === 'price_desc' ? ' selected' : '' ?>>Harga Tertinggi</option>
                        <option value="rating_asc"<?= $sort === 'rating_asc' ? ' selected' : '' ?>>Rating Terendah</option>
                        <option value="rating_desc"<?= $sort === 'rating_desc' ? ' selected' : '' ?>>Rating Tertinggi</option>
                    </select>
                </div>

                <div>
                    <span class="label">Rentang Harga</span>
                    <div class="range-row">
                        <div class="range-field">
                            <label for="min">Min</label>
                            <input type="number" id="min" name="min" min="<?= $scaleMin ?>" max="<?= $scaleMax ?>" step="5000" value="<?= $minVal ?>" placeholder="Rp<?= $scaleMin ?>">
                        </div>
                        <div class="range-field">
                            <label for="max">Max</label>
                            <input type="number" id="max" name="max" min="<?= $scaleMin ?>" max="<?= $scaleMax ?>" step="5000" value="<?= $maxVal ?>" placeholder="Rp<?= $scaleMax ?>">
                        </div>
                    </div>
                </div>
            </form>

            <?php if (count($products) > 0) : ?>
                <div class="grid">
                    <?php foreach ($products as $product) : ?>
                        <a class="card" href="<?= esc(site_url('product/' . $product['id'])) ?>">
                            <div class="thumb">
                                <img src="<?= esc($product['photos'][0]) ?>" alt="<?= esc($product['name']) ?>" loading="lazy">
                            </div>
                            <div class="meta">
                                <div class="name"><?= esc($product['name']) ?></div>
                                <div class="price">Rp<?= esc(number_format((float) $product['price'], 0, ',', '.')) ?></div>
                                <div class="rating">&#9733; <?= esc(number_format((float) $product['rating'], 1, ',', '.')) ?></div>
                            </div>
                        </a>
                    <?php endforeach ?>
                </div>
            <?php else : ?>
                <p class="empty">Belum ada produk.</p>
            <?php endif ?>
        </main>
    </div>

    <script>
        var form = document.querySelector('.filters');
        var minInput = document.getElementById('min');
        var maxInput = document.getElementById('max');

        document.getElementById('sort').addEventListener('change', function () {
            form.submit();
        });

        minInput.addEventListener('change', function () {
            if (minInput.value !== '' && maxInput.value !== '' && parseInt(minInput.value, 10) > parseInt(maxInput.value, 10)) {
                minInput.value = maxInput.value;
            }
            form.submit();
        });

        maxInput.addEventListener('change', function () {
            if (minInput.value !== '' && maxInput.value !== '' && parseInt(maxInput.value, 10) < parseInt(minInput.value, 10)) {
                maxInput.value = minInput.value;
            }
            form.submit();
        });
    </script>
</body>
</html>