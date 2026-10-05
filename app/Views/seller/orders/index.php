<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$buckets = [
    'all'        => 'Semua',
    'to_ship'    => 'Perlu dikerjakan',
    'in_transit' => 'Dalam pengiriman',
    'done'       => 'Selesai',
    'cancelled'  => 'Dibatalkan',
];
?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a> &rsaquo; Pesanan masuk
</p>

<div class="lp-section__head">
    <div>
        <h1>Pesanan Masuk</h1>
        <p class="lp-muted">Pesi yang masuk ke toko Anda, dari pembayaran sampai pengiriman.</p>
    </div>
</div>

<nav class="lp-filter-bar" aria-label="Saring status pesanan">
    <?php foreach ($buckets as $key => $label): ?>
        <a class="lp-chip <?= $status === $key ? 'is-active' : '' ?>"
           href="<?= route_to('seller_orders') ?>?status=<?= esc((string) $key) ?>"
           <?= $status === $key ? 'aria-current="page"' : '' ?>>
            <?= esc($label) ?>
            <span class="lp-small">(<?= esc((string) ($counts[$key] ?? 0)) ?>)</span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($orders === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada pesanan di saringan ini.</strong></p>
        <p class="lp-small">
            Pesanan baru akan muncul begitu pembeli menyelesaikan pembayaran.
        </p>
        <a class="lp-btn lp-btn--outline" href="<?= route_to('seller_products') ?>">Lihat produk saya</a>
    </div>
<?php else: ?>
    <div class="lp-table-wrap">
        <table class="lp-table">
            <thead>
                <tr>
                    <th scope="col">Nomor pesanan</th>
                    <th scope="col">Pembeli</th>
                    <th scope="col">Dibuat</th>
                    <th scope="col" class="lp-num">Nilai</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="lp-visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order):
                    $state = match ((string) $order['status']) {
                        'cancelled', 'refunded'   => 'bad',
                        'pending_payment'         => 'warn',
                        'shipped', 'delivered'    => 'ok',
                        default                   => 'warn',
                    };
                ?>
                    <tr>
                        <td>
                            <strong><?= esc((string) $order['order_number']) ?></strong>
                            <?php if (! empty($order['items'])): ?>
                                <div class="lp-small lp-muted">
                                    <?= esc((string) count($order['items'])) ?> jenis produk
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) ($order['ship_recipient_name'] ?? '—')) ?></td>
                        <td class="lp-small lp-muted"><?= esc(format_tanggal((string) $order['placed_at'])) ?></td>
                        <td class="lp-num"><?= esc(rupiah($order['grand_total'])) ?></td>
                        <td>
                            <span class="lp-pill lp-pill--<?= esc($state) ?>">
                                <?= esc((string) $order['status_label']) ?>
                            </span>
                        </td>
                        <td>
                            <a class="lp-btn lp-btn--sm lp-btn--outline"
                               href="<?= route_to('seller_order_show', (int) $order['id']) ?>">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav class="lp-pagination" aria-label="Halaman pesanan">
        <?php if ($page > 1): ?>
            <a href="?<?= esc(http_build_query(['status' => $status, 'page' => $page - 1])) ?>">Sebelumnya</a>
        <?php endif; ?>

        <span class="is-current">Halaman <?= esc((string) $page) ?></span>

        <?php if (count($orders) === 20): ?>
            <a href="?<?= esc(http_build_query(['status' => $status, 'page' => $page + 1])) ?>">Berikutnya</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<?= $this->endSection() ?>
