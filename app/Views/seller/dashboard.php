<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo;
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a>
</p>

<div class="lp-section__head">
    <div>
        <h1>
            Halo, <?= esc((string) ($shop['profile']['owner_name'] ?: $shop['profile']['display_name'])) ?>
        </h1>
        <p class="lp-muted">
            Ringkasan toko
            <?= esc((string) $shop['profile']['display_name']) ?>
            dalam 30 hari terakhir.
        </p>
    </div>

    <div class="lp-row" style="flex-wrap:wrap;">
        <?php if (($shop['profile']['is_active'] ?? 0) === 1): ?>
            <a class="lp-btn lp-btn--outline" href="<?= route_to('storefront', (string) $shop['profile']['slug']) ?>">
                Lihat toko saya
            </a>
        <?php else: ?>
            <span class="lp-pill lp-pill--warn">Toko belum aktif</span>
        <?php endif; ?>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('seller_product_create') ?>">Tambah produk</a>
    </div>
</div>

<div class="lp-stats">
    <div class="lp-panel">
        <p class="lp-small lp-muted">Omzet 30 hari</p>
        <p class="lp-price"><?= rupiah($revenue_30d) ?></p>
        <p class="lp-small lp-muted">Dari pesanan terkirim dan selesai</p>
    </div>

    <div class="lp-panel">
        <p class="lp-small lp-muted">Pesanan masuk</p>
        <p class="lp-price"><?= esc((string) count($orders['all'])) ?></p>
        <p class="lp-small lp-muted">
            <?= esc((string) count($orders['to_ship'])) ?> perlu dikirim
        </p>
    </div>

    <div class="lp-panel">
        <p class="lp-small lp-muted">Kunjungan toko</p>
        <p class="lp-price"><?= esc((string) $visit_count) ?></p>
        <p class="lp-small lp-muted"><?= esc((string) $product_views) ?> dilihat produk</p>
    </div>

    <div class="lp-panel">
        <p class="lp-small lp-muted">Percakapan</p>
        <p class="lp-price"><?= esc((string) $chat_started) ?></p>
        <p class="lp-small lp-muted">
            <?php if ($unread_chat > 0): ?>
                <a href="<?= route_to('chat_index') ?>"><?= esc((string) $unread_chat) ?> pesan belum dibaca</a>
            <?php else: ?>
                Semua pesan sudah dibaca
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="lp-split">
    <div class="lp-stack">
        <section class="lp-panel">
            <div class="lp-panel__head">
                <h2>Perlu ditangani</h2>
                <a class="lp-btn lp-btn--sm lp-btn--ghost" href="<?= route_to('seller_orders') ?>">
                    Semua pesanan
                </a>
            </div>

            <?php if ($orders['to_ship'] === [] && $orders['in_transit'] === []): ?>
                <p class="lp-muted">
                    Belum ada pesanan yang menunggu. Pesanan baru akan muncul di sini setelah pembeli memesan.
                </p>
            <?php else: ?>
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <caption class="lp-visually-hidden">Pesanan yang perlu ditangani</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nomor</th>
                                <th scope="col">Pembeli</th>
                                <th scope="col">Status</th>
                                <th scope="col">Nilai</th>
                                <th scope="col"><span class="lp-visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_merge($orders['to_ship'], $orders['in_transit']) as $order): ?>
                                <tr>
                                    <td>
                                        <a href="<?= route_to('seller_order_show', (string) $order['id']) ?>">
                                            <?= esc((string) $order['order_number']) ?>
                                        </a>
                                        <br>
                                        <span class="lp-small lp-muted">
                                            <?= format_tanggal($order['placed_at']) ?>
                                        </span>
                                    </td>
                                    <td><?= esc((string) ($order['customer_name'] ?? 'Pembeli')) ?></td>
                                    <td><?= status_pesan_pill($order['status']) ?></td>
                                    <td><?= rupiah($order['grand_total']) ?></td>
                                    <td>
                                        <a class="lp-btn lp-btn--sm lp-btn--outline"
                                           href="<?= route_to('seller_order_show', (string) $order['id']) ?>">
                                            Kelola
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="lp-panel">
            <div class="lp-panel__head">
                <h2>Stok menipis</h2>
                <a class="lp-btn lp-btn--sm lp-btn--ghost" href="<?= route_to('seller_products') ?>">
                    Kelola produk
                </a>
            </div>

            <?php if ($low_stock === []): ?>
                <p class="lp-muted">Semua stok masih di atas ambang batas.</p>
            <?php else: ?>
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <caption class="lp-visually-hidden">Produk dengan stok menipis</caption>
                        <thead>
                            <tr>
                                <th scope="col">Produk</th>
                                <th scope="col">Sisa stok</th>
                                <th scope="col">Ambang</th>
                                <th scope="col"><span class="lp-visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ((int) ($product['stock'] ?? 0) > 0): ?>
                                            <span><?= esc((string) $product['name']) ?></span>
                                        <?php else: ?>
                                            <strong><?= esc((string) $product['name']) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['made_to_order'] ?? false): ?>
                                            <span class="lp-tag lp-tag--made">Pesanan khusus</span>
                                        <?php else: ?>
                                            <span class="lp-tag lp-tag--out"><?= esc((string) $product['stock']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc((string) $product['low_stock_threshold']) ?></td>
                                    <td>
                                        <a class="lp-btn lp-btn--sm lp-btn--outline"
                                           href="<?= route_to('seller_product_edit', (string) $product['id']) ?>">
                                            Ubah
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="lp-stack">
        <section class="lp-panel">
            <h2>Menu Mitra</h2>

            <div class="lp-stack" style="gap:.5rem;">
                <a class="lp-btn lp-btn--block lp-btn--outline" href="<?= route_to('seller_products') ?>">
                    Produk saya (<?= esc((string) $product_count) ?>)
                </a>
                <a class="lp-btn lp-btn--block lp-btn--outline" href="<?= route_to('seller_shop') ?>">
                    Edit toko &amp; cerita
                </a>
                <a class="lp-btn lp-btn--block lp-btn--outline" href="<?= route_to('seller_blogs') ?>">
                    Artikel saya (<?= esc((string) $story_count) ?>)
                </a>
                <a class="lp-btn lp-btn--block lp-btn--outline" href="<?= route_to('seller_orders') ?>">
                    Pesanan masuk
                </a>
                <a class="lp-btn lp-btn--block lp-btn--outline" href="<?= route_to('chat_index') ?>">
                    Percakapan
                </a>
            </div>
        </section>

        <section class="lp-panel">
            <h2>Dari mana pembeli datang</h2>

            <?php if ($geography === []): ?>
                <p class="lp-small lp-muted">Belum ada data pengiriman.</p>
            <?php else: ?>
                <?php
            // Bar widths are proportional to the busiest destination, so the
            // first row always fills the bar and the rest scale against it.
            // `buyerGeography()` returns `regency` + `order_count`.
            $geoMax = 0;

            foreach ($geography as $row) {
                $geoMax = max($geoMax, (int) $row['order_count']);
            }
            ?>
                <div class="lp-stack" style="gap:.5rem;">
                    <?php foreach ($geography as $row): ?>
                        <?php
                        $geoWidth = $geoMax > 0
                            ? (int) round(((int) $row['order_count'] / $geoMax) * 100)
                            : 0;
                        ?>
                        <div>
                            <div class="lp-row" style="justify-content:space-between;gap:.5rem;">
                                <span class="lp-small"><?= esc((string) $row['regency']) ?></span>
                                <span class="lp-small lp-muted">
                                    <?= esc((string) $row['order_count']) ?> pesanan
                                </span>
                            </div>
                            <div style="height:.5rem;border-radius:999px;background:var(--lp-line);overflow:hidden;">
                                <div style="height:100%;width:<?= esc((string) $geoWidth) ?>%;background:var(--lp-accent);"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?= $this->endSection() ?>
