<?php

/**
 * One customer's order, with its delivery, payment and scan timeline.
 *
 * @var array<string, mixed> $order
 * @var string|null $message
 */

use App\Services\Rupiah;

$title       = 'Pesanan ' . $order['order_code'];
$current     = 'checkout';
$bodyClass   = 'page-order';
$stylesheets = ['assets/css/checkout.css'];
$description = 'Detail pesanan ' . $order['order_code'] . ' beserta status pengiriman dan pembayaran.';

$delivery = $order['delivery'];
$payment  = $order['payment'];

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
    'description' => $description,
]);
?>

<?= view('partials/masthead', ['current' => $current]) ?>

<main class="lok-shell page-main">
    <nav class="breadcrumb" aria-label="Remah roti">
        <ol>
            <li><a href="<?= esc(base_url('orders'), 'attr') ?>">Pesanan Saya</a></li>
            <li aria-current="page"><?= esc((string) $order['order_code']) ?></li>
        </ol>
    </nav>

    <header class="page-head page-head--row">
        <div>
            <h1>Pesanan <?= esc((string) $order['order_code']) ?></h1>
            <p class="page-head__lede">
                Dibuat <?= esc(date('j F Y, H:i', strtotime((string) $order['created_at']))) ?>
            </p>
        </div>
        <p class="badge badge--<?= esc((string) $order['status'], 'attr') ?>">
            <?= esc(ucfirst(str_replace('_', ' ', (string) $order['status']))) ?>
        </p>
    </header>

    <?php if ($message !== null) : ?>
        <p class="notice notice--ok" role="status"><?= esc($message) ?></p>
    <?php endif ?>

    <div class="order-layout">

        <div class="order-main">
            <section class="panel" aria-labelledby="order-items">
                <h2 id="order-items">Karya yang Dipesan</h2>

                <ul class="summary-items">
                    <?php foreach ($order['items'] as $item) : ?>
                        <li class="summary-item">
                            <span class="summary-item__body">
                                <span class="summary-item__name"><?= esc($item['product_name']) ?></span>
                                <span class="summary-item__meta">
                                    <?= esc($item['variant_name']) ?> &times; <?= (int) $item['qty'] ?>
                                    &middot; <?= esc(Rupiah::format((int) $item['unit_price'])) ?> / buah
                                </span>
                            </span>
                            <span class="summary-item__price"><?= esc(Rupiah::format((int) $item['line_total'])) ?></span>
                        </li>
                    <?php endforeach ?>
                </ul>

                <dl class="totals">
                    <div>
                        <dt>Subtotal</dt>
                        <dd><?= esc(Rupiah::format((int) $order['subtotal'])) ?></dd>
                    </div>
                    <div>
                        <dt>Ongkos kirim</dt>
                        <dd><?= esc(Rupiah::format((int) $order['delivery_fee'])) ?></dd>
                    </div>
                    <?php if ((int) $order['discount_amount'] > 0) : ?>
                        <div class="totals__row--minus">
                            <dt>Diskon</dt>
                            <dd>&minus; <?= esc(Rupiah::format((int) $order['discount_amount'])) ?></dd>
                        </div>
                    <?php endif ?>
                    <div class="totals__row--grand">
                        <dt>Total</dt>
                        <dd><?= esc(Rupiah::format((int) $order['total'])) ?></dd>
                    </div>
                </dl>
            </section>

            <?php if ($delivery !== null) : ?>
                <section class="panel" aria-labelledby="order-delivery">
                    <h2 id="order-delivery">Pengiriman</h2>

                    <dl class="detail-list">
                        <div>
                            <dt>Layanan</dt>
                            <dd><?= esc((string) ($delivery['courier_label'] ?? $delivery['service_type'])) ?></dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd><?= esc(ucfirst(str_replace('_', ' ', (string) $delivery['status']))) ?></dd>
                        </div>
                        <?php if ($delivery['tracking_code'] !== null && $delivery['tracking_code'] !== '') : ?>
                            <div>
                                <dt>Nomor resi</dt>
                                <dd><?= esc((string) $delivery['tracking_code']) ?></dd>
                            </div>
                        <?php endif ?>
                        <div>
                            <dt>Diminta</dt>
                            <dd><?= esc(date('j F Y, H:i', strtotime((string) $delivery['requested_at']))) ?></dd>
                        </div>
                    </dl>
                </section>
            <?php endif ?>

            <?php if ($order['events'] !== []) : ?>
                <section class="panel" aria-labelledby="order-timeline">
                    <h2 id="order-timeline">Riwayat Perjalanan</h2>

                    <ol class="timeline">
                        <?php foreach ($order['events'] as $event) : ?>
                            <li class="timeline__item">
                                <span class="timeline__dot" aria-hidden="true"></span>
                                <span class="timeline__body">
                                    <span class="timeline__status">
                                        <?= esc(ucfirst(str_replace('_', ' ', (string) $event['status']))) ?>
                                    </span>
                                    <?php if ($event['note'] !== null && $event['note'] !== '') : ?>
                                        <span class="timeline__note"><?= esc($event['note']) ?></span>
                                    <?php endif ?>
                                    <span class="timeline__time">
                                        <?= esc(date('j F Y, H:i', strtotime((string) $event['created_at']))) ?>
                                    </span>
                                </span>
                            </li>
                        <?php endforeach ?>
                    </ol>
                </section>
            <?php endif ?>
        </div>

        <aside class="order-side">
            <section class="panel" aria-labelledby="order-address">
                <h2 id="order-address">Alamat Pengiriman</h2>
                <address class="address">
                    <strong><?= esc((string) $order['recipient_name']) ?></strong><br>
                    <?= esc((string) $order['recipient_phone']) ?><br>
                    <?= esc((string) $order['address_line']) ?>
                    <?php if ($order['subdistrict'] !== null && $order['subdistrict'] !== '') : ?>
                        <br><?= esc((string) $order['subdistrict']) ?>
                    <?php endif ?><br>
                    <?= esc((string) $order['city']) ?>
                    <?php if ($order['postal_code'] !== null && $order['postal_code'] !== '') : ?>
                        <?= esc((string) $order['postal_code']) ?>
                    <?php endif ?>
                </address>

                <?php if ($order['courier_note'] !== null && $order['courier_note'] !== '') : ?>
                    <p class="address__note"><strong>Catatan kurir:</strong> <?= esc((string) $order['courier_note']) ?></p>
                <?php endif ?>
            </section>

            <?php if ($payment !== null) : ?>
                <section class="panel" aria-labelledby="order-payment">
                    <h2 id="order-payment">Pembayaran</h2>

                    <dl class="detail-list">
                        <div>
                            <dt>Metode</dt>
                            <dd><?= esc((string) ($payment['method_name'] ?? $payment['method_code'])) ?></dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd><?= esc(ucfirst(str_replace('_', ' ', (string) $payment['status']))) ?></dd>
                        </div>
                        <div>
                            <dt>Jumlah</dt>
                            <dd><?= esc(Rupiah::format((int) $payment['amount'])) ?></dd>
                        </div>
                        <?php if ($payment['reference'] !== null && $payment['reference'] !== '') : ?>
                            <div>
                                <dt>Referensi</dt>
                                <dd><?= esc((string) $payment['reference']) ?></dd>
                            </div>
                        <?php endif ?>
                        <?php if ($payment['expires_at'] !== null && $payment['expires_at'] !== '') : ?>
                            <div>
                                <dt>Bayar sebelum</dt>
                                <dd><?= esc(date('j F Y, H:i', strtotime((string) $payment['expires_at']))) ?></dd>
                            </div>
                        <?php endif ?>
                    </dl>

                    <?php if ((string) $payment['status'] === 'pending') : ?>
                        <p class="payment-pending">
                            <?= esc((string) ($payment['method_instructions'] ?? 'Selesaikan pembayaran sesuai instruksi metode pilihanmu.')) ?>
                        </p>
                    <?php endif ?>

                    <p class="escrow-note">
                        Dana ditahan platform sampai kamu mengonfirmasi karya diterima.
                    </p>
                </section>
            <?php endif ?>

            <a class="btn btn--quiet btn--block" href="<?= esc(base_url('orders'), 'attr') ?>">
                Semua pesanan
            </a>
        </aside>
    </div>
</main>

<?= view('partials/footer') ?>
<?= view('partials/document-end') ?>