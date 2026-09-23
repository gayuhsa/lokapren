<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($product['name']) ?></title>
    <style>
        body { font-family: sans-serif; max-width: 60em; margin: 3rem auto; padding: 0 1rem; }
        .notice-temp { background: #e0f2fe; border: 1px solid #38bdf8; color: #075985; padding: .5rem; border-radius: 4px; }
        .top { text-align: right; }
        .top a, .buy { font: inherit; text-decoration: none; }
        .back { color: #2563eb; }
        .detail { display: flex; gap: 2rem; margin-top: 1.5rem; align-items: flex-start; }
        .gallery { flex: 1 1 55%; }
        .viewport { position: relative; aspect-ratio: 1 / 1; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; }
        .viewport img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0, 0, 0, .4); color: #fff; border: none; font-size: 2rem; line-height: 1; padding: .25rem .7rem; cursor: pointer; border-radius: 4px; }
        .nav:hover { background: rgba(0, 0, 0, .7); }
        .nav-prev { left: .5rem; }
        .nav-next { right: .5rem; }
        .thumbs { display: flex; gap: .5rem; margin-top: .5rem; }
        .thumb { flex: 0 0 72px; border: 2px solid transparent; border-radius: 4px; padding: 0; cursor: pointer; background: none; overflow: hidden; }
        .thumb img { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; display: block; }
        .thumb.active { border-color: #2563eb; }
        .info { flex: 1 1 45%; }
        .info h1 { margin: 0 0 .5rem; }
        .price { font-size: 1.5rem; font-weight: bold; color: #2563eb; margin: .5rem 0 .25rem; }
        .rating { color: #a16207; margin: 0 0 1rem; }
        .description { line-height: 1.5; color: #444; margin-bottom: 1.5rem; }
        .buy { display: inline-block; background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: .75rem 2rem; font: inherit; font-weight: bold; cursor: pointer; }
        .buy:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <p class="notice-temp"><strong>Temporary placeholder frontend.</strong> This page will be replaced by a proper design.</p>
    <div class="top"><a class="back" href="<?= esc(site_url('marketplace')) ?>">&larr; Kembali ke marketplace</a></div>

    <div class="detail">
        <div class="gallery">
            <div class="viewport">
                <button type="button" class="nav nav-prev" aria-label="Previous photo">&lsaquo;</button>
                <img id="mainImage" src="<?= esc($product['photos'][0]) ?>" alt="<?= esc($product['name']) ?>">
                <button type="button" class="nav nav-next" aria-label="Next photo">&rsaquo;</button>
            </div>
            <div class="thumbs">
                <?php foreach ($product['photos'] as $i => $photo) : ?>
                    <button type="button" class="thumb<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
                        <img src="<?= esc($photo) ?>" alt="<?= esc($product['name']) ?> photo <?= $i + 1 ?>" loading="lazy">
                    </button>
                <?php endforeach ?>
            </div>
        </div>
        <div class="info">
            <h1><?= esc($product['name']) ?></h1>
            <p class="price">Rp<?= esc(number_format((float) $product['price'], 0, ',', '.')) ?></p>
            <p class="rating">&#9733; <?= esc(number_format((float) $product['rating'], 1, ',', '.')) ?></p>
            <p class="description"><?= esc($product['description']) ?></p>
            <button type="button" class="buy">Beli Sekarang</button>
        </div>
    </div>

    <script>
        var photos = <?= json_encode(array_values($product['photos']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var main = document.getElementById('mainImage');
        var thumbs = Array.prototype.slice.call(document.querySelectorAll('.thumb'));
        var index = 0;

        function show(i) {
            if (i < 0) i = photos.length - 1;
            if (i >= photos.length) i = 0;
            index = i;
            main.src = photos[index];
            thumbs.forEach(function (thumb, j) {
                thumb.classList.toggle('active', j === index);
            });
        }

        document.querySelector('.nav-prev').addEventListener('click', function () { show(index - 1); });
        document.querySelector('.nav-next').addEventListener('click', function () { show(index + 1); });
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () { show(parseInt(thumb.dataset.index, 10)); });
        });
    </script>
</body>
</html>