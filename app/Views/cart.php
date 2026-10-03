<?php

/**
 * The customer's cart. Every price here was re-derived from product_variants
 * by App\Services\Cart when the page was built.
 *
 * @var array<string, mixed> $cart
 * @var string|null         $message
 */

use App\Services\Rupiah;

$title       = 'Keranjang Belanja';
$current     = 'checkout';
$bodyClass   = 'page-cart';
$stylesheets = ['assets/css/checkout.css'];
$description = 'Keranjang belanja karya kriya pilihan pengrajin Lokapren.';

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
    'description' => $description,
]);
?>

<?= view('partials/masthead', ['current' => $current]) ?>

<main class="lok-shell page-main">
    <header class="page-head">
        <h1>Keranjang Belanja</h1>
        <p class="page-head__lede">
            <?php if ($cart['count'] > 0) : ?>
                <?= $cart['count'] ?> karya dari <?= count($cart['stores']) ?> sanggar, siap dikirim langsung dari bilik pengrajin.
            <?php else : ?>
                Belum ada karya di keranjangmu.
            <?php endif ?>
        </p>
    </header>

    <?php if ($message !== null) : ?>
        <p class="notice notice--ok" role="status"><?= esc($message) ?></p>
    <?php endif ?>

    <?php if ($message === null && $cart['issues'] !== []) : ?>
        <div class="notice notice--warn" role="alert">
            <p>Beberapa baris perlu diperbaiki sebelum checkout:</p>
            <ul>
                <?php foreach ($cart['issues'] as $issue) : ?>
                    <li><?= esc($issue) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <?php if ($cart['items'] === []) : ?>
        <section class="empty-state">
            <h2>Keranjangmu masih kosong</h2>
            <p>Temukan relief, gerabah, atau kain yang dikerjakan langsung oleh pengrajin Magelang.</p>
            <a class="btn btn--primary" href="<?= esc(base_url('marketplace'), 'attr') ?>">Jelajahi Katalog</a>
        </section>
    <?php else : ?>
        <div class="cart-layout">

            <section class="cart-lines" aria-label="Baris keranjang">
                <?php foreach ($cart['items'] as $item) : ?>
                    <article class="cart-line">
                        <?php if ($item['image_url'] !== null) : ?>
                            <img class="cart-line__thumb"
                                 src="<?= esc((string) $item['image_url'], 'attr') ?>"
                                 alt=""
                                 width="72" height="72" loading="lazy">
                        <?php else : ?>
                            <span class="cart-line__thumb cart-line__thumb--empty" aria-hidden="true"></span>
                        <?php endif ?>

                        <div class="cart-line__body">
                            <h3 class="cart-line__name">
                                <a href="<?= esc(base_url('product/' . $item['product_slug']), 'attr') ?>">
                                    <?= esc($item['product_name']) ?>
                                </a>
                            </h3>
                            <p class="cart-line__meta">
                                <?= esc($item['variant_name']) ?>
                                &middot;
                                <a href="<?= esc(base_url('store/' . $item['store_slug']), 'attr') ?>"><?= esc($item['store_name']) ?></a>
                            </p>

                            <?php if (! $item['sellable']) : ?>
                                <p class="cart-line__warn">Baris ini belum bisa dipesan. Hapus atau kurangi jumlahnya.</p>
                            <?php endif ?>

                            <div class="cart-line__controls">
                                <form action="<?= esc(site_url('cart/line/' . $item['id']), 'attr') ?>" method="post" class="qty-form">
                                    <?= csrf_field() ?>
                                    <label class="lok-visually-hidden" for="qty-<?= (int) $item['id'] ?>">
                                        Jumlah <?= esc($item['product_name']) ?>
                                    </label>
                                    <input id="qty-<?= (int) $item['id'] ?>" type="number"
                                           name="qty" value="<?= (int) $item['qty'] ?>"
                                           min="0" max="<?= (int) $item['stock'] ?>" step="1">
                                    <button type="submit" class="btn btn--ghost btn--sm">Perbarui</button>
                                </form>

                                <form action="<?= esc(site_url('cart/line/' . $item['id'] . '/remove'), 'attr') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--quiet btn--sm">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <p class="cart-line__total">
                            <span class="cart-line__unit"><?= esc(Rupiah::format($item['unit_price'])) ?> / buah</span>
                            <strong><?= esc(Rupiah::format($item['line_total'])) ?></strong>
                        </p>
                    </article>
                <?php endforeach ?>
            </section>

            <aside class="cart-summary" aria-label="Ringkasan keranjang">
                <h2>Ringkasan</h2>

                <dl class="totals">
                    <div>
                        <dt>Subtotal (<?= $cart['count'] ?> karya)</dt>
                        <dd><?= esc(Rupiah::format($cart['subtotal'])) ?></dd>
                    </div>
                    <div>
                        <dt>Pengiriman</dt>
                        <dd>Dihitung saat checkout</dd>
                    </div>
                </dl>

                <p class="cart-summary__note">
                    Biaya pengiriman, vouchermitra, dan total akhir dihitung server-side
                    pada langkah checkout.
                </p>

                <a class="btn btn--primary btn--block" href="<?= esc(site_url('checkout'), 'attr') ?>">
                    Lanjut ke Checkout
                </a>
                <a class="btn btn--quiet btn--block" href="<?= esc(base_url('marketplace'), 'attr') ?>">
                    Cari karya lain
                </a>
            </aside>
        </div>
    <?php endif ?>
</main>

<?= view('partials/footer') ?>
<?= view('partials/document-end') ?>