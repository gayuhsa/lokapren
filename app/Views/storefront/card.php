<?php

use App\Services\Rupiah;

/**
 * One work in the storefront grid.
 *
 * @var array<string,mixed> $product
 * @var int                 $heading
 */

$heading = $heading ?? 3;

$price     = (int) ($product['min_price'] ?? 0);
$sold      = (int) ($product['sold_count'] ?? 0);
$rating    = (float) ($product['rating_average'] ?? 0);
$rated     = (int) ($product['rating_count'] ?? 0) > 0;
$stock     = (int) ($product['total_stock'] ?? 0);
$buyable   = ($product['min_price_in_stock'] ?? null) !== null;
$href      = base_url('product/' . $product['slug']);
$origin    = trim((string) ($product['store_city'] ?? ''));
$origin    = preg_replace('/^(Kabupaten|Kota)\s+/iu', '', $origin) ?: 'Magelang';
$shortDesc = trim(preg_replace('/\s+/', ' ', (string) ($product['description'] ?? '')) ?? '');
?>
<article class="sf-card">
    <a class="sf-card__media" href="<?= esc($href, 'attr') ?>" tabindex="-1" aria-hidden="true">
        <?php if (! empty($product['image_url'])) : ?>
            <img src="<?= base_url($product['image_url']) ?>"
                 alt="<?= esc($product['image_alt'] ?? $product['name']) ?>"
                 loading="lazy">
        <?php else : ?>
            <span class="sf-card__media-empty">Foto menyusul</span>
        <?php endif ?>

        <span class="sf-card__origin">Asli <?= esc($origin) ?></span>

        <?php if (! empty($product['category_name'])) : ?>
            <span class="sf-card__craft"><?= esc($product['category_name']) ?></span>
        <?php endif ?>
    </a>

    <div class="sf-card__body">
        <p class="sf-card__rating">
            <?php if ($rated) : ?>
                <span class="sf-card__stars">
                    <?= esc(number_format($rating, 1, ',', '.')) ?>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
                        <path d="M12 2.5l2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.35 6.19 20.4l1.11-6.47-4.7-4.58 6.5-.95L12 2.5z"/>
                    </svg>
                </span>
            <?php endif ?>

            <span class="sf-card__sold">
                <?= $sold > 0 ? 'Terjual ' . $sold . ' karya' : 'Belum terjual' ?>
            </span>
        </p>

        <h<?= $heading ?> class="sf-card__title">
            <a href="<?= esc($href, 'attr') ?>"><?= esc($product['name']) ?></a>
        </h<?= $heading ?>>

        <?php if ($shortDesc !== '') : ?>
            <p class="sf-card__desc"><?= esc($shortDesc) ?></p>
        <?php endif ?>

        <?php if (! $buyable) : ?>
            <p class="stock stock--out">Stok habis</p>
        <?php endif ?>

        <div class="sf-card__foot">
            <div>
                <p class="sf-card__price-label">Harga Karya</p>
                <p class="sf-card__price">
                    <?= $price > 0 ? esc(Rupiah::format($price)) : 'Harga menyusul' ?>
                </p>
            </div>

            <a class="sf-card__more" href="<?= esc($href, 'attr') ?>">
                Lihat Detail
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </a>
        </div>
    </div>
</article>