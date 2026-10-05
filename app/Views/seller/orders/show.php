<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$state = match ((string) $order['status']) {
    'cancelled', 'refunded' => 'bad',
    'completed', 'delivered' => 'ok',
    'shipped'             => 'ok',
    default               => 'warn',
};

$shipment  = $order['shipment'] ?? null;
$isShipped = $order['status'] === 'shipped';
?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a> &rsaquo;
    <a href="<?= route_to('seller_orders') ?>">Pesanan masuk</a> &rsaquo;
    <?= esc((string) $order['order_number']) ?>
</p>

<div class="lp-section__head">
    <div>
        <h1><?= esc((string) $order['order_number']) ?></h1>
        <p class="lp-muted">
            Masuk <?= esc(format_tanggal((string) $order['placed_at'], true)) ?>
            &middot; <?= esc((string) ($order['ship_recipient_name'] ?? 'pembeli')) ?>
        </p>
    </div>
    <span class="lp-pill lp-pill--<?= esc($state) ?>"><?= esc((string) $order['status_label']) ?></span>
</div>

<div class="lp-split">
    <div>
        <div class="lp-panel">
            <h2>Produk yang harus dikerjakan</h2>

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
                                                 alt="" width="56" height="56" loading="lazy">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= esc((string) $item['product_name']) ?></strong>
                                            <?php if (! empty($item['variant_label'])): ?>
                                                <br><small class="lp-muted"><?= esc((string) $item['variant_label']) ?></small>
                                            <?php endif; ?>
                                            <?php if (! empty($item['note'])): ?>
                                                <br><small class="lp-muted">
                                                    Catatan pembeli: <?= esc((string) $item['note']) ?>
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
                    <span>Ongkos kirim</span>
                    <span><?= esc(rupiah($order['shipping_total'])) ?></span>
                </div>
                <div class="lp-totals__row lp-totals__row--grand">
                    <span>Diterima Anda</span>
                    <span><?= esc(rupiah($order['grand_total'])) ?></span>
                </div>
            </div>
        </div>

        <?php if (! empty($order['customer_note'])): ?>
            <div class="lp-panel">
                <h2>Catatan pembeli</h2>
                <p><?= esc((string) $order['customer_note']) ?></p>
            </div>
        <?php endif; ?>

        <div class="lp-panel">
            <h2>Alamat pengiriman</h2>
            <p>
                <strong><?= esc((string) $order['ship_recipient_name']) ?></strong>
                &middot; <?= esc((string) $order['ship_recipient_phone']) ?><br>
                <?= esc((string) $order['ship_address_line']) ?><br>
                <span class="lp-muted">
                    <?= esc(lokasi_teks($order, ', ', 'ship_')) ?>
                    <?= ! empty($order['ship_postal_code']) ? esc((string) $order['ship_postal_code']) : '' ?>
                </span>
                <?php if (! empty($order['ship_landmark'])): ?>
                    <br><span class="lp-muted">Patokan: <?= esc((string) $order['ship_landmark']) ?></span>
                <?php endif; ?>
            </p>
        </div>

        <div class="lp-panel">
            <h2>Riwayat status</h2>
            <?php if ($order['history'] === []): ?>
                <p class="lp-muted">Belum ada riwayat.</p>
            <?php else: ?>
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
            <?php endif; ?>
        </div>
    </div>

    <aside class="lp-aside-sticky">
        <div class="lp-panel">
            <h2>Ubah status</h2>

            <?php if ($order['actions'] === []): ?>
                <p class="lp-muted">
                    Pesanan ini sudah selesai atau tidak bisa diubah lagi dari sini.
                </p>
            <?php else: ?>
                <p class="lp-small lp-muted">
                    Hanya langkah berikutnya yang ditampilkan. Sistem menolak lompatan status.
                </p>

                <form method="post" action="<?= route_to('seller_order_status', (int) $order['id']) ?>" class="lp-form">
                    <?= csrf_field() ?>

                    <div class="lp-field">
                        <label for="to_status">Langkah berikutnya</label>
                        <select id="to_status" name="to_status" required>
                            <?php foreach ($order['actions'] as $action): ?>
                                <option value="<?= esc((string) $action['value']) ?>">
                                    <?= esc((string) $action['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($order['status'] === 'awaiting_artisan' || $order['status'] === 'in_production'): ?>
                        <div class="lp-field">
                            <label for="production_progress">Kemajuan pengerjaan (%)</label>
                            <input id="production_progress" name="production_progress" type="number"
                                   min="0" max="100"
                                   value="<?= esc((string) old('production_progress', $order['production_progress'] ?? 0)) ?>">
                            <small class="lp-small lp-muted">
                                Angka yang dilihat pembeli di halaman lacak pesanan.
                            </small>
                        </div>
                    <?php endif; ?>

                    <div class="lp-field">
                        <label for="note">Catatan untuk pembeli</label>
                        <input id="note" name="note" maxlength="255"
                               placeholder="Misalnya: ukiran sudah dikunci warna">
                    </div>

                    <div class="lp-field">
                        <label for="tracking_number">Nomor resi</label>
                        <input id="tracking_number" name="tracking_number" maxlength="80"
                               value="<?= esc((string) old('tracking_number', $shipment['tracking_number'] ?? '')) ?>"
                               <?= $isShipped ? '' : 'disabled' ?>>
                        <small class="lp-small lp-muted">
                            <?= $isShipped
                                ? 'Resi tersimpan saat pesanan ditandai dikirim.'
                                : 'Aktif saat pesanan ditandai dikirim.' ?>
                        </small>
                    </div>

                    <button class="lp-btn lp-btn--solid lp-btn--block" type="submit">Simpan perubahan status</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($shipment !== null && ! empty($shipment['tracking_number'])): ?>
            <div class="lp-panel" style="margin-top:1rem;">
                <h2>Pengiriman</h2>
                <table class="lp-table">
                    <tbody>
                        <?php if (! empty($shipment['courier_name'])): ?>
                            <tr><th scope="row">Kurir</th><td><?= esc((string) $shipment['courier_name']) ?></td></tr>
                        <?php endif; ?>
                        <tr><th scope="row">Resi</th><td><?= esc((string) $shipment['tracking_number']) ?></td></tr>
                        <?php if (! empty($shipment['shipped_at'])): ?>
                            <tr>
                                <th scope="row">Dikirim</th>
                                <td><?= esc(format_tanggal((string) $shipment['shipped_at'])) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?= $this->endSection() ?>
