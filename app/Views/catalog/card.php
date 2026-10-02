<?php

use App\Services\Rupiah;

/**
 * A single catalogue card.
 *
 * Expects a row shaped like CatalogQuery::listingColumns(): min_price and
 * total_stock come from subqueries, so the card never needs a second query.
 *
 * @var array<string,mixed> $product
 * @var bool               $showStore
 * @var int                $heading
 */

$showStore = $showStore ?? true;
$heading   = $heading ?? 3;

$price     = (int) ($product['min_price'] ?? 0);
$stock     = (int) ($product['total_stock'] ?? 0);
$inStock   = ($product['min_price_in_stock'] ?? null) !== null;
$href      = base_url('product/' . $product['slug']);
?>
<article class="card">
    <a class="card__media" href="<?= esc($href, 'attr') ?>" tabindex="-1" aria-hidden="true">
        <?php if (! empty($product['image_url'])) : ?>
            <img src="<?= base_url($product['image_url']) ?>"
                 alt="<?= esc($product['image_alt'] ?? $product['name']) ?>"
                 loading="lazy">
        <?php else : ?>
            <span class="card__media-empty">Foto menyusul</span>
        <?php endif ?>
    </a>

    <?php if ((bool) ($product['is_featured'] ?? false)) : ?>
        <p class="card__flag">Pilihan</p>
    <?php endif ?>

    <div class="card__body">
        <?php if ($showStore && ! empty($product['store_name'])) : ?>
            <p class="card__store"><?= esc($product['store_name']) ?></p>
        <?php endif ?>

        <h<?= $heading ?> class="card__title">
            <a href="<?= esc($href, 'attr') ?>"><?= esc($product['name']) ?></a>
        </h<?= $heading ?>>

        <?php if (! empty($product['category_name'])) : ?>
            <p class="card__category"><?= esc($product['category_name']) ?></p>
        <?php endif ?>

        <p class="card__price">
            <?php if ($price > 0) : ?>
                <?= esc(Rupiah::format($price)) ?>
                <span class="card__price-note">mulai</span>
            <?php else : ?>
                <span class="card__price-note">Harga menyusul</span>
            <?php endif ?>
        </p>

        <p class="card__stock">
            <?php if (! $inStock) : ?>
                <span class="stock stock--out">Stok habis</span>
            <?php elseif ($stock > 0) : ?>
                <span class="stock">Stok tersedia</span>
            <?php endif ?>
        </p>
    </div>
</article>