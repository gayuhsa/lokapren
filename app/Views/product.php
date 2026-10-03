<?php

use App\Services\Rupiah;

$title       = $product['name'];
$current     = 'detail';
$bodyClass   = 'page-product';
$stylesheets = ['assets/css/catalog.css'];
$description = (string) mb_substr((string) $product['description'], 0, 155);

$images    = $product['images'];
$variants  = $product['variants'];
$rating    = $product['rating'];
$selected  = $product['default_variant'];
$heroImage = $images[0] ?? null;

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
    'description' => $description,
]);
?>

<?= view('partials/masthead', ['current' => $current]) ?>

<main class="lok-shell product">

    <nav class="breadcrumb" aria-label="Remah roti">
        <ol>
            <li><a href="<?= base_url() ?>">Beranda</a></li>
            <li><a href="<?= base_url('marketplace') ?>">Katalog</a></li>
            <?php if ($product['category_name']) : ?>
                <li><a href="<?= base_url('marketplace') . '?' . http_build_query(['category' => $product['category_slug']]) ?>">
                    <?= esc($product['category_name']) ?></a></li>
            <?php endif ?>
            <li><span><?= esc($product['store_name']) ?></span></li>
            <li aria-current="page"><?= esc($product['name']) ?></li>
        </ol>
    </nav>

    <div class="product__top">

        <div class="product__media">

            <?php if ($heroImage !== null) : ?>
                <figure class="product__hero">
                    <img src="<?= base_url($heroImage['url']) ?>"
                         alt="<?= esc($heroImage['alt'] ?? $product['name']) ?>">
                </figure>
            <?php endif ?>

            <?php if (count($images) > 1) : ?>
                <ul class="thumbs">
                    <?php foreach ($images as $index => $image) : ?>
                        <li>
                            <img src="<?= base_url($image['url']) ?>"
                                 alt="<?= esc($image['alt'] ?? $product['name']) ?>"
                                 loading="lazy">
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>

            <p class="product__story">
                <span class="eyebrow">Warisan pengrajin</span>
                <?= esc($product['description']) ?>
            </p>

            <?php if (! empty($product['store_tagline'])) : ?>
                <p class="product__store-line">
                    <strong><?= esc($product['store_name']) ?></strong>
                    <span><?= esc($product['store_tagline']) ?></span>
                </p>
            <?php endif ?>
        </div>

        <div class="product__summary">
            <p class="eyebrow">Kriya Terpilih</p>
            <h1 class="product__title"><?= esc($product['name']) ?></h1>

            <?php if ($selected !== null) : ?>
                <p class="product__variant-name"><?= esc($selected['name']) ?></p>
            <?php endif ?>

            <?php if ($rating['count'] > 0) : ?>
                <p class="product__rating">
                    <strong><?= number_format($rating['average'], 1, ',', '.') ?></strong>
                    <span>(<?= $rating['count'] ?> ulasan)</span>
                </p>
            <?php endif ?>

            <div class="price">
                <?php if ($product['price'] > 0) : ?>
                    <p class="price__now"><?= esc(Rupiah::format($product['price'])) ?></p>

                    <?php if ($product['compare_at'] > $product['price']) : ?>
                        <p class="price__was">
                            <s><?= esc(Rupiah::format($product['compare_at'])) ?></s>
                            <span>Harga kriya langsung pengrajin</span>
                        </p>
                    <?php endif ?>
                <?php else : ?>
                    <p class="price__now">Harga menyusul</p>
                <?php endif ?>
            </div>

            <?php if ($variants !== []) : ?>
                <fieldset class="variants">
                    <legend>Pilih Ukuran Relief:</legend>

                    <div class="variants__list">
                        <?php foreach ($variants as $variant) : ?>
                            <?php
                            $soldOut = (int) $variant['stock'] <= 0;
                            $isSel   = $selected !== null && (int) $variant['id'] === (int) $selected['id'];
                            ?>
                            <label class="variant<?= $soldOut ? ' is-soldout' : '' ?>">
                                <input type="radio" name="variant" value="<?= (int) $variant['id'] ?>"
                                       <?= $isSel ? 'checked' : '' ?> <?= $soldOut ? 'disabled' : '' ?>>
                                <span class="variant__name"><?= esc($variant['name']) ?></span>
                                <span class="variant__price"><?= esc(Rupiah::format($variant['price'])) ?></span>
                                <?php if ($soldOut) : ?>
                                    <span class="variant__flag">Habis</span>
                                <?php endif ?>
                            </label>
                        <?php endforeach ?>
                    </div>
                </fieldset>
            <?php endif ?>

            <div class="actions">
                <?php if (! $product['in_stock']) : ?>
                    <p class="actions__note">Stok sedang habis. Hubungi pengrajin untuk Availability.</p>
                <?php elseif (auth()->loggedIn()) : ?>
                    <?php /*
                     * A POST carrying a CSRF token, not a link: adding to a cart
                     * changes state on the signed-in customer's own cart. The
                     * price shown here is informational; the server re-derives it.
                     */ ?>
                    <form action="<?= esc(site_url('cart/add'), 'attr') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="variant_id"
                               value="<?= (int) ($selected['id'] ?? 0) ?>">
                        <input type="hidden" name="qty" value="1">
                        <button class="btn btn--primary btn--block"
                                type="submit"
                                <?= $selected === null ? 'disabled' : '' ?>>
                            Pesan Sekarang
                        </button>
                    </form>
                    <p class="actions__note">Pemesanan dikirim ke tahap checkout.</p>
                <?php else : ?>
                    <?php /* Starting a thread is a POST with a CSRF token, and the
                    store is taken from the product row on the server, never from a
                    submitted store_id. */ ?>
                    <form action="<?= esc(site_url('chat/store/' . (int) $product['store_id']), 'attr') ?>" method="post">
                        <?= csrf_field() ?>
                        <button class="btn btn--ghost btn--block" type="submit">Tanya Pengrajin</button>
                    </form>
                    <a class="btn btn--primary btn--block" href="<?= esc(site_url('login'), 'attr') ?>">
                        Masuk untuk Pesan
                    </a>
                    <p class="actions__note">Masuk dulu, lalu pemesanan dikirim ke tahap checkout.</p>
                <?php endif ?>
            </div>

            <dl class="spec">
                <div>
                    <dt>Material Baku</dt>
                    <dd><?= esc($product['specs']['material'] ?? 'Kayu pilihan') ?></dd>
                </div>
                <div>
                    <dt>Finishing Pahat</dt>
                    <dd><?= esc($product['specs']['finishing'] ?? 'Alami') ?></dd>
                </div>
                <div>
                    <dt>Asal</dt>
                    <dd><?= esc($product['specs']['origin'] ?? ($product['store_city'] ?? 'Magelang')) ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <section class="cert">
        <h2 class="cert__title">Garansi Keaslian Budaya</h2>
        <p>Disertai sertifikat keaslian ber-QR digital. Tiap karya terdaftar kriptografis
            menjamin keaslian <?= esc(mb_strtolower((string) $product['store_name'])) ?>.</p>
        <p class="cert__code">Kode: <?= esc($product['store_code'] . '-' . strtoupper(substr((string) $product['slug'], 0, 3))) ?></p>
    </section>

    <section class="assurance">
        <h2 class="assurance__title">Proteksi Pengiriman &amp; Asuransi Kriya</h2>
        <p>Garansi penggantian 100% jika terjadi retak atau cacat dalam pengiriman.</p>
    </section>

    <section class="trustbar" aria-label="Jaminan produk">
        <ul>
            <li>Dibuat 100% manual</li>
            <li>Kayu legal</li>
            <li>Pembayaran aman</li>
            <li>Bersertifikat</li>
            <li>Garansi kerajinan otentik</li>
        </ul>
    </section>

    <section class="reviews" aria-labelledby="reviews-heading">
        <div class="reviews__head">
            <div>
                <h2 id="reviews-heading">Ulasan Kurasi &amp; Saksi Mutu</h2>
                <p>
                    <?php if ($rating['count'] > 0) : ?>
                        Dari <?= $rating['count'] ?> ulasan terverifikasi.
                    <?php else : ?>
                        Belum ada ulasan untuk karya ini.
                    <?php endif ?>
                </p>
            </div>
        </div>

        <?php if ($rating['count'] > 0) : ?>
            <ul class="reviews__breakdown">
                <?php for ($star = 5; $star >= 1; $star--) : ?>
                    <?php
                    $count   = $rating['breakdown'][$star] ?? 0;
                    $percent = $rating['count'] > 0 ? round($count / $rating['count'] * 100) : 0;
                    ?>
                    <li>
                        <span class="reviews__star"><?= $star ?> Bintang</span>
                        <progress class="reviews__bar" value="<?= $count ?>"
                                  max="<?= max(1, $rating['count']) ?>"></progress>
                        <span class="reviews__pct"><?= $percent ?>% (<?= $count ?>)</span>
                    </li>
                <?php endfor ?>
            </ul>
        <?php endif ?>

        <?php if ($reviews === []) : ?>
            <p class="empty">Belum ada ulasan yang terverifikasi untuk karya ini.</p>
        <?php else : ?>
            <ul class="reviews__list">
                <?php foreach ($reviews as $review) : ?>
                    <li class="review">
                        <p class="review__meta">
                            <strong><?= esc($review['reviewer_name']) ?></strong>
                            <span><?= $review['rating'] ?>/5</span>
                        </p>
                        <?php if (! empty($review['comment'])) : ?>
                            <p class="review__body">&ldquo;<?= esc($review['comment']) ?>&rdquo;</p>
                        <?php endif ?>
                        <?php if ((int) $review['helpful_count'] > 0) : ?>
                            <p class="review__helpful"><?= (int) $review['helpful_count'] ?> membantu</p>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <?php if ($related !== []) : ?>
        <section class="related" aria-labelledby="related-heading">
            <h2 id="related-heading">Karya Lain di Kategori Ini</h2>

            <div class="grid">
                <?php foreach ($related as $item) : ?>
                    <?= view('catalog/card', [
                        'product'   => $item,
                        'showStore' => true,
                        'heading'   => 3,
                    ]) ?>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>

</main>

<?= view('partials/footer') ?>

<?= view('partials/document-end') ?>
