<?php

/**
 * A product card, used on the home page, catalog grid and storefront.
 *
 * @var array<string, mixed> $product
 * @var string|null $search
 */
$cover   = $product['cover_path'] ?? null;
$shopSlug = $product['shop_slug'] ?? null;
?>
<article class="lp-card lp-card--product">
    <a class="lp-card__media" href="<?= route_to('product', (string) $product['slug']) ?>">
        <?php if ($cover !== null): ?>
            <img src="<?= esc((string) url_gambar($cover)) ?>"
                 alt="<?= esc((string) $product['name']) ?>"
                 loading="lazy"
                 width="400" height="300">
        <?php else: ?>
            <span class="lp-card__placeholder" aria-hidden="true">Kriya</span>
        <?php endif; ?>

        <?php if ((int) ($product['made_to_order'] ?? 0) === 1): ?>
            <span class="lp-tag lp-tag--made">Dibuat Pesanan</span>
        <?php elseif ((int) ($product['stock'] ?? 0) > 0): ?>
            <span class="lp-tag lp-tag--stock">Stok <?= esc((string) $product['stock']) ?></span>
        <?php else: ?>
            <span class="lp-tag lp-tag--out">Habis</span>
        <?php endif; ?>
    </a>

    <div class="lp-card__body">
        <?php if ($shopSlug !== null): ?>
            <a class="lp-card__shop" href="<?= route_to('storefront', (string) $shopSlug) ?>">
                <?= esc((string) ($product['shop_name'] ?? 'Sanggar')) ?>
            </a>
        <?php endif; ?>

        <h3 class="lp-card__title">
            <a href="<?= route_to('product', (string) $product['slug']) ?>">
                <?= esc((string) $product['name']) ?>
            </a>
        </h3>

        <?php if (! empty($product['subtitle'])): ?>
            <p class="lp-card__text"><?= esc((string) $product['subtitle']) ?></p>
        <?php endif; ?>

        <div class="lp-card__foot">
            <strong class="lp-price"><?= esc(rupiah($product['price'])) ?></strong>
            <?php if (! empty($product['shop_rating_count'])): ?>
                <?= bintang($product['shop_rating'] ?? 0, $product['shop_rating_count']) ?>
            <?php endif; ?>
        </div>
    </div>
</article>
