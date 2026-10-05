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
    <a href="<?= route_to('orders') ?>">Pesanan</a> &rsaquo;
    <a href="<?= route_to('order_show', (int) $order['id']) ?>">
        <?= esc((string) $order['order_number']) ?>
    </a> &rsaquo; Lacak
</p>

<h1>Lacak Pengiriman</h1>

<div class="lp-split">
    <div>
        <div class="lp-panel">
            <div class="lp-panel__head">
                <div>
                    <strong><?= esc((string) $order['order_number']) ?></strong>
                    <br><span class="lp-muted lp-small">
                        <?= esc((string) $order['courier_name']) ?>
                        <?php if (! empty($order['shipment']['service_level'])): ?>
                            &middot; <?= esc((string) $order['shipment']['service_level']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <span class="lp-pill lp-pill--<?= esc($state) ?>"><?= esc((string) $order['status_label']) ?></span>
            </div>

            <?php if (! empty($order['shipment']['tracking_number'])): ?>
                <p>
                    <strong>Nomor resi:</strong>
                    <span style="font-family:var(--lp-mono, monospace);"><?= esc((string) $order['shipment']['tracking_number']) ?></span>
                </p>
            <?php else: ?>
                <p class="lp-muted">
                    Nomor resi belum tersedia. Sanggar akan mengisinya saat paket diserahkan ke kurir.
                </p>
            <?php endif; ?>
        </div>

        <div class="lp-panel">
            <h2>Riwayat</h2>

            <?php if ($order['history'] === []): ?>
                <p class="lp-muted lp-small">Belum ada pembaruan status.</p>
            <?php else: ?>
                <ol class="lp-stack" style="list-style:none;padding:0;margin:0;">
                    <?php foreach (array_reverse($order['history']) as $entry): ?>
                        <li>
                            <strong>
                                <?= esc((string) (\App\Services\OrderService::statusLabels()[$entry['to_status']] ?? $entry['to_status'])) ?>
                            </strong>
                            <br>
                            <small class="lp-muted">
                                <?= esc(format_tanggal((string) $entry['created_at'], true)) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>

    <aside class="lp-aside-sticky">
        <div class="lp-panel">
            <h2>Tujuan</h2>
            <p class="lp-small">
                <strong><?= esc((string) $order['ship_recipient_name']) ?></strong><br>
                <?= esc((string) $order['ship_address_line']) ?><br>
                <span class="lp-muted"><?= esc(lokasi_teks($order, ', ', 'ship_')) ?></span>
            </p>

            <?php if (! empty($order['ship_latitude']) && ! empty($order['ship_longitude'])): ?>
                <p class="lp-small">
                    <a href="https://www.openstreetmap.org/?mlat=<?= esc((string) $order['ship_latitude']) ?>&mlon=<?= esc((string) $order['ship_longitude']) ?>#map=15/<?= esc((string) $order['ship_latitude']) ?>/<?= esc((string) $order['ship_longitude']) ?>"
                       target="_blank" rel="noopener noreferrer">
                        Lihat di Peta &rarr;
                    </a>
                </p>
            <?php endif; ?>

            <?php if (! empty($order['estimated_delivery_at'])): ?>
                <p class="lp-small lp-muted">
                    Perkiraan tiba: <?= esc(format_tanggal((string) $order['estimated_delivery_at'])) ?>
                </p>
            <?php endif; ?>

            <a class="lp-btn lp-btn--outline lp-btn--block" style="margin-top:1rem;"
               href="<?= route_to('order_show', (int) $order['id']) ?>">Kembali ke detail pesanan</a>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
