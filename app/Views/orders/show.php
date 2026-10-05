<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$state = match ((string) $order['status']) {
    'cancelled', 'refunded' => 'bad',
    'pending_payment'       => 'warn',
    default                 => 'ok',
};
?>

<p class="lp-breadcrumb">
    <a href="<?= site_url('account') ?>">Akun</a> &rsaquo;
    <a href="<?= route_to('orders') ?>">Pesanan</a> &rsaquo;
    <?= esc((string) $order['order_number']) ?>
</p>

<div class="lp-section__head">
    <div>
        <h1><?= esc((string) $order['order_number']) ?></h1>
        <p class="lp-muted">
            Dibuat <?= esc(format_tanggal((string) $order['placed_at'], true)) ?>
            <?php if (! empty($order['shop_name'])): ?>
                &middot; dari
                <a href="<?= route_to('storefront', (string) $order['shop_slug']) ?>">
                    <?= esc((string) $order['shop_name']) ?>
                </a>
            <?php endif; ?>
        </p>
    </div>
    <span class="lp-pill lp-pill--<?= esc($state) ?>"><?= esc((string) $order['status_label']) ?></span>
</div>

<?php if ($order['progress']['step'] > 0): ?>
    <div class="lp-rail" style="margin-bottom:1.5rem;">
        <?php
        $steps = [
            ['label' => 'Pesanan dibuat', 'step' => 1],
            ['label' => 'Diproses sanggar', 'step' => 2],
            ['label' => 'Siap dikirim', 'step' => 3],
            ['label' => 'Dalam pengiriman', 'step' => 4],
            ['label' => $order['status'] === 'completed' ? 'Selesai' : 'Diterima', 'step' => 5],
        ];
        $current = (int) $order['progress']['step'];
        ?>
        <?php foreach ($steps as $step): ?>
            <div class="lp-rail__step <?= $step['step'] <= $current ? 'is-done' : '' ?>">
                <span class="lp-rail__dot" aria-hidden="true"></span>
                <span class="lp-rail__label"><?= esc($step['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="lp-split">
    <div>
        <div class="lp-panel">
            <h2>Produk dipesan</h2>

            <div class="lp-table-wrap">
                <table class="lp-table">
                    <thead>
                        <tr>
                            <th scope="col">Karya</th>
                            <th scope="col" class="lp-num">Harga</th>
                            <th scope="col" class="lp-num">Jumlah</th>
                            <th scope="col" class="lp-num">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td>
                                    <div class="lp-row" style="flex-wrap:nowrap;align-items:flex-start;">
                                        <?php if (! empty($item['image_path'])): ?>
                                            <img src="<?= esc((string) url_gambar($item['image_path'])) ?>"
                                                 alt="" width="56" height="56">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= esc((string) $item['product_name']) ?></strong>
                                            <?php if (! empty($item['variant_label'])): ?>
                                                <br><small class="lp-muted">
                                                    <?= esc((string) $item['variant_label']) ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (! empty($item['note'])): ?>
                                                <br><small class="lp-muted">
                                                    Catatan: <?= esc((string) $item['note']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="lp-num"><?= esc(rupiah($item['unit_price'])) ?></td>
                                <td class="lp-num"><?= esc((string) $item['quantity']) ?></td>
                                <td class="lp-num"><?= esc(rupiah($item['subtotal'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="lp-totals" style="margin-top:1rem;">
                <div class="lp-totals__row">
                    <span>Subtotal</span>
                    <span><?= esc(rupiah($order['subtotal'])) ?></span>
                </div>
                <div class="lp-totals__row">
                    <span>Ongkos kirim (<?= esc((string) $order['courier_name']) ?>)</span>
                    <span><?= esc(rupiah($order['shipping_total'])) ?></span>
                </div>
                <div class="lp-totals__row lp-totals__row--grand">
                    <span>Total</span>
                    <span><?= esc(rupiah($order['grand_total'])) ?></span>
                </div>
            </div>
        </div>

        <div class="lp-panel">
            <h2>Alamat pengiriman</h2>
            <p>
                <strong><?= esc((string) $order['ship_recipient_name']) ?></strong>
                &middot; <?= esc((string) $order['ship_recipient_phone']) ?><br>
                <?= esc((string) $order['ship_address_line']) ?><br>
                <span class="lp-muted">
                    <?= esc(lokasi_teks($order, ', ', 'ship_')) ?>
                    <?= $order['ship_postal_code'] !== null && $order['ship_postal_code'] !== ''
                        ? esc((string) $order['ship_postal_code']) : '' ?>
                </span>
                <?php if (! empty($order['ship_landmark'])): ?>
                    <br><span class="lp-muted">Patokan: <?= esc((string) $order['ship_landmark']) ?></span>
                <?php endif; ?>
            </p>

            <?php if (! empty($order['customer_note'])): ?>
                <p class="lp-small"><strong>Catatan Anda:</strong> <?= esc((string) $order['customer_note']) ?></p>
            <?php endif; ?>
        </div>

        <div class="lp-panel">
            <h2>Riwayat status</h2>
            <ol class="lp-stack" style="list-style:none;padding:0;margin:0;">
                <?php foreach (array_reverse($order['history']) as $entry): ?>
                    <li>
                        <strong><?= esc((string) $entry['to_status']) ?></strong>
                        <?php if (! empty($entry['note'])): ?>
                            &mdash; <?= esc((string) $entry['note']) ?>
                        <?php endif; ?>
                        <br>
                        <small class="lp-muted"><?= esc(format_tanggal((string) $entry['created_at'], true)) ?></small>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <?php if ($can_review): ?>
            <div class="lp-panel">
                <h2>Beri ulasan</h2>
                <p class="lp-small lp-muted">
                    Ulasan Anda membantu pembeli lain menilai karya ini.
                </p>

                <?php foreach ($order['items'] as $item): ?>
                    <?php if (in_array((int) $item['id'], $order['reviewed_item_ids'] ?? [], true)): ?>
                        <div class="lp-small lp-muted" style="border-top:1px solid var(--lp-line);padding-top:1rem;margin-top:1rem;">
                            <strong><?= esc((string) $item['product_name']) ?></strong> &mdash; ulasan sudah terkirim. Terima kasih!
                        </div>
                        <?php continue; ?>
                    <?php endif; ?>
                    <form method="post" action="<?= route_to('review_store', (int) $order['id']) ?>"
                          style="border-top:1px solid var(--lp-line);padding-top:1rem;margin-top:1rem;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="order_item_id" value="<?= esc((string) $item['id']) ?>">

                        <div class="lp-field">
                            <label for="rating-<?= esc((string) $item['id']) ?>">
                                Rating untuk <strong><?= esc((string) $item['product_name']) ?></strong>
                            </label>
                            <select id="rating-<?= esc((string) $item['id']) ?>" name="rating" required>
                                <option value="5">5 &mdash; Sangat puas</option>
                                <option value="4">4 &mdash; Puas</option>
                                <option value="3">3 &mdash; Cukup</option>
                                <option value="2">2 &mdash; Kurang</option>
                                <option value="1">1 &mdash; Tidak puas</option>
                            </select>
                        </div>

                        <div class="lp-field">
                            <label for="body-<?= esc((string) $item['id']) ?>">Ceritakan pengalaman Anda</label>
                            <textarea id="body-<?= esc((string) $item['id']) ?>" name="body" rows="3"
                                      maxlength="1000" required></textarea>
                        </div>

                        <button class="lp-btn lp-btn--solid lp-btn--sm" type="submit">Kirim ulasan</button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="lp-aside-sticky">
        <div class="lp-panel">
            <h2>Pesanan</h2>

            <table class="lp-table">
                <tbody>
                    <tr><th scope="row">Status</th><td><?= esc((string) $order['status_label']) ?></td></tr>
                    <tr>
                        <th scope="row">Pembayaran</th>
                        <td>
                            <?= $order['payment_status'] === 'paid'
                                ? '<span class="lp-pill lp-pill--ok">Lunas</span>'
                                : '<span class="lp-pill lp-pill--warn">Belum dibayar</span>' ?>
                        </td>
                    </tr>
                    <?php if (! empty($order['shipment']['courier_name'])): ?>
                        <tr><th scope="row">Kurir</th><td><?= esc((string) $order['shipment']['courier_name']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (! empty($order['shipment']['tracking_number'])): ?>
                        <tr><th scope="row">Resi</th><td><?= esc((string) $order['shipment']['tracking_number']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (! empty($order['estimated_delivery_at'])): ?>
                        <tr>
                            <th scope="row">Perkiraan tiba</th>
                            <td><?= esc(format_tanggal((string) $order['estimated_delivery_at'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <a class="lp-btn lp-btn--outline lp-btn--block" style="margin-top:1rem;"
               href="<?= route_to('order_tracking', (int) $order['id']) ?>">Lacak pengiriman</a>

            <form method="post" action="<?= route_to('order_chat', (int) $order['id']) ?>" style="margin-top:.5rem;">
                <?= csrf_field() ?>
                <button class="lp-btn lp-btn--ghost lp-btn--block" type="submit">Chat pengrajin</button>
            </form>

            <?php if (in_array($order['status'], ['pending_payment', 'awaiting_artisan', 'in_production', 'ready_to_ship'], true)): ?>
                <form method="post" action="<?= route_to('order_cancel', (int) $order['id']) ?>"
                      style="margin-top:.5rem;" data-confirm="Batalkan pesanan ini?">
                    <?= csrf_field() ?>
                    <div class="lp-field">
                        <label for="reason">Alasan pembatalan (opsional)</label>
                        <input id="reason" type="text" name="reason" maxlength="255"
                               placeholder="Misalnya: salah pilih ukuran">
                    </div>
                    <button class="lp-btn lp-btn--ghost lp-btn--block" type="submit">Batalkan pesanan</button>
                </form>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
