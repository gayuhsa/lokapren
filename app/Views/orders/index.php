<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url('account') ?>">Akun</a> &rsaquo; Pesanan Saya
</p>

<div class="lp-section__head">
    <div>
        <h1>Pesanan Saya</h1>
        <p class="lp-muted">Riwayat pesanan dari setiap sanggar yang pernah Anda pesan.</p>
    </div>
    <a class="lp-btn lp-btn--outline lp-btn--sm" href="<?= route_to('catalog') ?>">Belanja lagi</a>
</div>

<?php
$bucketLabels = [
    'all'        => 'Semua',
    'unpaid'     => 'Menunggu bayar',
    'processing' => 'Diproses',
    'shipped'    => 'Dikirim',
    'done'       => 'Selesai',
    'cancelled'  => 'Dibatalkan',
];
?>

<nav class="lp-thread-list" style="margin-bottom:1.5rem;flex-wrap:wrap;">
    <?php foreach ($bucketLabels as $key => $label): ?>
        <?php if (! array_key_exists($key, $buckets)): ?>
            <?php continue; ?>
        <?php endif; ?>
        <?php
        // `route_to()` only builds path segments, so the filter is appended as
        // a query string instead of being smuggled in as a segment.
        $tabUrl = route_to('orders') . ($key === 'all' ? '' : '?status=' . rawurlencode($key));
        ?>
        <a class="lp-chip"
           href="<?= esc($tabUrl) ?>"
           <?= $status === $key ? 'aria-current="page" style="background:var(--lp-clay);color:#fff;"' : '' ?>>
            <?= esc($label) ?>
            <?php if (! empty($counts[$key])): ?>
                <span class="lp-badge"><?= esc((string) $counts[$key]) ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($orders === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada pesanan di sini.</strong></p>
        <p class="lp-small">Cari karya yang ingin Anda bawa pulang.</p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('catalog') ?>">Jelajahi Katalog</a>
    </div>
<?php else: ?>
    <div class="lp-stack">
        <?php foreach ($orders as $order): ?>
            <?php
            $state = match ((string) $order['status']) {
                'cancelled', 'refunded' => 'bad',
                'pending_payment'       => 'warn',
                default                 => 'ok',
            };
            ?>
            <div class="lp-card">
                <div class="lp-card__body">
                    <div class="lp-row" style="justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                        <div>
                            <strong><?= esc((string) $order['order_number']) ?></strong>
                            <br>
                            <span class="lp-muted lp-small">
                                <?= esc(format_tanggal((string) $order['placed_at'], true)) ?>
                                <?php if (! empty($order['ship_regency'])): ?>
                                    &middot; kirim ke <?= esc((string) $order['ship_regency']) ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="lp-row" style="gap:.5rem;">
                            <span class="lp-pill lp-pill--<?= esc($state) ?>">
                                <?= esc((string) (\App\Services\OrderService::statusLabels()[$order['status']] ?? $order['status'])) ?>
                            </span>
                            <strong class="lp-num"><?= esc(rupiah($order['grand_total'])) ?></strong>
                        </div>
                    </div>

                    <?php if (! empty($order['shop_name'])): ?>
                        <p class="lp-small" style="margin-top:.5rem;">
                            Sanggar:
                            <?php if (! empty($order['shop_slug'])): ?>
                                <a href="<?= route_to('storefront', (string) $order['shop_slug']) ?>">
                                    <?= esc((string) $order['shop_name']) ?>
                                </a>
                            <?php else: ?>
                                <?= esc((string) $order['shop_name']) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="lp-card__foot">
                    <a class="lp-btn lp-btn--sm lp-btn--outline"
                       href="<?= route_to('order_show', (int) $order['id']) ?>">Lihat detail</a>
                    <a class="lp-btn lp-btn--sm lp-btn--ghost"
                       href="<?= route_to('order_tracking', (int) $order['id']) ?>">Lacak pengiriman</a>
                    <form method="post" action="<?= route_to('order_chat', (int) $order['id']) ?>">
                        <?= csrf_field() ?>
                        <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Chat pengrajin</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="lp-pagination" aria-label="Halaman pesanan">
            <?php if ($page > 1): ?>
                <a href="?<?= esc(http_build_query(['status' => $status, 'page' => $page - 1])) ?>">Sebelumnya</a>
            <?php endif; ?>

            <span aria-current="page"><?= esc((string) $page) ?> / <?= esc((string) $pages) ?></span>

            <?php if ($page < $pages): ?>
                <a href="?<?= esc(http_build_query(['status' => $status, 'page' => $page + 1])) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
