<?php

/**
 * The checkout page.
 *
 * Nothing rendered here was taken from the request: App\Services\Checkout::quote()
 * re-derived every price, fee and discount from product_variants,
 * shipping_methods and promo_codes before this view was built.
 *
 * @var array<string, mixed> $quote
 * @var list<array<string, mixed>> $payments
 * @var string|null $message
 * @var string|null $error
 * @var array<string, mixed> $old
 */

use App\Services\Rupiah;

$cart     = $quote['cart'];
$methods  = $quote['methods'];
$totals   = $quote['totals'];
$promo    = $quote['promo'];

$title       = 'Checkout & Pengiriman';
$current     = 'checkout';
$bodyClass   = 'page-checkout';
$stylesheets = ['assets/css/checkout.css'];
$description = 'Selesaikan pesanan karya kriya dengan pengiriman antar terima dari pengrajin Magelang.';

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
        <h1>Checkout &amp; Pengiriman</h1>
        <p class="page-head__lede">
            Alamat tujuan, layanan antar terima, lalu pembayaran. Dana ditahan sampai karya diterima.
        </p>
    </header>

    <ol class="steps" aria-label="Langkah checkout">
        <li class="steps__item is-done"><span class="steps__num">1</span> Alamat Pengiriman</li>
        <li class="steps__item is-current"><span class="steps__num">2</span> Layanan &amp; Pembayaran</li>
        <li class="steps__item"><span class="steps__num">3</span> Konfirmasi</li>
    </ol>

    <?php if ($error !== null) : ?>
        <p class="notice notice--error" role="alert"><?= esc($error) ?></p>
    <?php endif ?>

    <?php if ($message !== null) : ?>
        <p class="notice notice--ok" role="status"><?= esc($message) ?></p>
    <?php endif ?>

    <?php if ($cart['issues'] !== []) : ?>
        <div class="notice notice--warn" role="alert">
            <p>Perbaiki keranjangmu sebelum melanjutkan:</p>
            <ul>
                <?php foreach ($cart['issues'] as $issue) : ?>
                    <li><?= esc($issue) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <?php if ($cart['items'] === []) : ?>
        <section class="empty-state">
            <h2>Tidak ada yang bisa di-checkout</h2>
            <p>Keranjangmu kosong, jadi belum ada karya yang bisa dipesan.</p>
            <a class="btn btn--primary" href="<?= esc(base_url('marketplace'), 'attr') ?>">Jelajahi Katalog</a>
        </section>
    <?php else : ?>
        <form class="checkout-layout" action="<?= esc(site_url('checkout/place'), 'attr') ?>" method="post">
            <?= csrf_field() ?>

            <div class="checkout-main">

                <section class="panel" aria-labelledby="step-address">
                    <h2 id="step-address"><span class="panel__num">1</span> Alamat Pengiriman</h2>

                    <div class="field-grid">
                        <p class="field">
                            <label for="recipient_name">Nama Penerima</label>
                            <input id="recipient_name" type="text" name="recipient_name" required
                                   maxlength="150"
                                   value="<?= esc((string) ($old['recipient_name'] ?? '')) ?>"
                                   autocomplete="name">
                        </p>

                        <p class="field">
                            <label for="recipient_phone">Nomor WhatsApp</label>
                            <input id="recipient_phone" type="tel" name="recipient_phone" required
                                   maxlength="20" inputmode="tel"
                                   placeholder="0812 3456 7890"
                                   value="<?= esc((string) ($old['recipient_phone'] ?? '')) ?>"
                                   autocomplete="tel">
                        </p>

                        <p class="field field--wide">
                            <label for="address_line">Alamat Lengkap</label>
                            <textarea id="address_line" name="address_line" rows="3" required
                                      maxlength="255"
                                      placeholder="Nama jalan, nomor rumah, RT/RW, patokan"
                                      autocomplete="street-address"><?= esc((string) ($old['address_line'] ?? '')) ?></textarea>
                        </p>

                        <p class="field">
                            <label for="subdistrict">Desa / kelurahan</label>
                            <input id="subdistrict" type="text" name="subdistrict" maxlength="100"
                                   value="<?= esc((string) ($old['subdistrict'] ?? '')) ?>">
                        </p>

                        <p class="field">
                            <label for="city">Kecamatan / kota</label>
                            <input id="city" type="text" name="city" required maxlength="100"
                                   value="<?= esc((string) ($old['city'] ?? '')) ?>">
                        </p>

                        <p class="field">
                            <label for="postal_code">Kode pos</label>
                            <input id="postal_code" type="text" name="postal_code" maxlength="10"
                                   inputmode="numeric"
                                   value="<?= esc((string) ($old['postal_code'] ?? '')) ?>">
                        </p>

                        <p class="field field--wide">
                            <label for="courier_note">Catatan untuk kurir <span class="field__opt">opsional</span></label>
                            <textarea id="courier_note" name="courier_note" rows="2" maxlength="500"
                                      placeholder="Contoh: titip ke pos keamanan, lewat gang kedua"><?= esc((string) ($old['courier_note'] ?? '')) ?></textarea>
                        </p>
                    </div>
                </section>

                <section class="panel" aria-labelledby="step-shipping">
                    <h2 id="step-shipping"><span class="panel__num">2</span> Layanan Pengiriman</h2>

                    <?php if ($methods === []) : ?>
                        <p class="notice notice--error">
                            Belum ada layanan pengiriman yang aktif. Hubungi marketplace.
                        </p>
                    <?php else : ?>
                        <div class="choice-list">
                            <?php foreach ($methods as $i => $method) : ?>
                                <label class="choice">
                                    <input type="radio" name="shipping_code"
                                           value="<?= esc((string) $method['code'], 'attr') ?>"
                                           <?= $i === 0 ? 'checked' : '' ?>
                                           required>
                                    <span class="choice__body">
                                        <span class="choice__title"><?= esc((string) $method['name']) ?></span>
                                        <span class="choice__desc"><?= esc((string) $method['description']) ?></span>
                                        <span class="choice__meta">
                                            <span><?= esc((string) $method['eta_text']) ?></span>
                                            <?php if ($method['packing_text'] !== null && $method['packing_text'] !== '') : ?>
                                                <span><?= esc((string) $method['packing_text']) ?></span>
                                            <?php endif ?>
                                        </span>
                                    </span>
                                    <span class="choice__price">
                                        <?= (int) $method['cost'] === 0 ? 'Gratis' : esc(Rupiah::format((int) $method['cost'])) ?>
                                    </span>
                                </label>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </section>

                <section class="panel" aria-labelledby="step-payment">
                    <h2 id="step-payment"><span class="panel__num">3</span> Metode Pembayaran</h2>

                    <?php if ($payments === []) : ?>
                        <p class="notice notice--error">Belum ada metode pembayaran yang aktif.</p>
                    <?php else : ?>
                        <div class="choice-list">
                            <?php foreach ($payments as $i => $payment) : ?>
                                <label class="choice">
                                    <input type="radio" name="payment_code"
                                           value="<?= esc((string) $payment['code'], 'attr') ?>"
                                           <?= $i === 0 ? 'checked' : '' ?>
                                           required>
                                    <span class="choice__body">
                                        <span class="choice__title"><?= esc((string) $payment['name']) ?></span>
                                        <span class="choice__desc"><?= esc((string) $payment['description']) ?></span>
                                    </span>
                                    <span class="choice__price">
                                        <?php if ((int) $payment['fee'] === 0) : ?>
                                            Tanpa biaya
                                        <?php else : ?>
                                            + <?= esc(Rupiah::format((int) $payment['fee'])) ?>
                                        <?php endif ?>
                                    </span>
                                </label>
                            <?php endforeach ?>
                        </div>

                        <p class="escrow-note">
                            <strong>Escrow Lokapren.</strong> Dana kamu ditahan di platform dan baru
                            diteruskan ke pengrajin setelah kamu mengonfirmasi karya diterima dengan baik.
                        </p>
                    <?php endif ?>
                </section>
            </div>

            <aside class="checkout-summary" aria-label="Ringkasan pesanan">
                <h2>Ringkasan Pesanan</h2>

                <ul class="summary-items">
                    <?php foreach ($cart['items'] as $item) : ?>
                        <li class="summary-item">
                            <?php if ($item['image_url'] !== null) : ?>
                                <img src="<?= esc((string) $item['image_url'], 'attr') ?>" alt=""
                                     width="48" height="48" loading="lazy">
                            <?php else : ?>
                                <span class="summary-item__thumb" aria-hidden="true"></span>
                            <?php endif ?>
                            <span class="summary-item__body">
                                <span class="summary-item__name"><?= esc($item['product_name']) ?></span>
                                <span class="summary-item__meta">
                                    <?= esc($item['variant_name']) ?> &times; <?= (int) $item['qty'] ?>
                                </span>
                            </span>
                            <span class="summary-item__price"><?= esc(Rupiah::format((int) $item['line_total'])) ?></span>
                        </li>
                    <?php endforeach ?>
                </ul>

                <?php if ($cart['stores'] !== []) : ?>
                    <p class="summary-stores">
                        Dipesan dari <?= count($cart['stores']) ?> sanggar:
                        <?= esc(implode(', ', array_column($cart['stores'], 'name'))) ?>.
                    </p>
                <?php endif ?>

                <form class="promo-form" action="<?= esc(site_url('checkout/promo'), 'attr') ?>" method="post">
                    <?= csrf_field() ?>
                    <label for="promo_code">Kode promo</label>
                    <div class="promo-form__row">
                        <input id="promo_code" type="text" name="code" maxlength="40"
                               placeholder="LOKALBANGGA"
                               value="<?= esc((string) ($quote['promoCode'] ?? ''), 'attr') ?>"
                               <?= $promo !== null ? 'disabled' : '' ?>>
                        <?php if ($promo !== null) : ?>
                            <button type="submit" name="code" value="hapus" class="btn btn--quiet btn--sm">Lepas</button>
                        <?php else : ?>
                            <button type="submit" class="btn btn--ghost btn--sm">Pakai</button>
                        <?php endif ?>
                    </div>
                    <?php if ($promo !== null) : ?>
                        <p class="promo-form__ok"><?= esc($promo['label']) ?> sudah terpasang.</p>
                    <?php endif ?>
                </form>

                <dl class="totals">
                    <div>
                        <dt>Subtotal</dt>
                        <dd><?= esc(Rupiah::format((int) $totals['subtotal'])) ?></dd>
                    </div>
                    <div>
                        <dt>Ongkos kirim</dt>
                        <dd>dihitung setelah memilih layanan</dd>
                    </div>
                    <?php if ((int) $totals['discount'] > 0) : ?>
                        <div class="totals__row--minus">
                            <dt>Diskon <?= esc((string) $totals['promo_code']) ?></dt>
                            <dd>&minus; <?= esc(Rupiah::format((int) $totals['discount'])) ?></dd>
                        </div>
                    <?php endif ?>
                    <div class="totals__row--grand">
                        <dt>Total sementara</dt>
                        <dd><?= esc(Rupiah::format((int) $totals['grand_total'])) ?></dd>
                    </div>
                </dl>

                <p class="summary-note">
                    Total final dihitung ulang di server saat pesanan dibuat. Harga di katalog
                    adalah harga langsung dari pengrajin.
                </p>

                <button class="btn btn--primary btn--block" type="submit" <?= $methods === [] || $payments === [] ? 'disabled' : '' ?>>
                    Buat Pesanan
                </button>

                <a class="btn btn--quiet btn--block" href="<?= esc(site_url('cart'), 'attr') ?>">Kembali ke keranjang</a>
            </aside>
        </form>
    <?php endif ?>
</main>

<?= view('partials/footer') ?>
<?= view('partials/document-end') ?>