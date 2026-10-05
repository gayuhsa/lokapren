<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a> &rsaquo; Produk saya
</p>

<div class="lp-section__head">
    <div>
        <h1>Produk Saya</h1>
        <p class="lp-muted">
            <?= esc((string) $total) ?> produk tersimpan. Hanya produk berstatus terbit yang tampil di katalog.
        </p>
    </div>

    <a class="lp-btn lp-btn--solid" href="<?= route_to('seller_product_create') ?>">Tambah produk</a>
</div>

<?php if ($products === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada produk.</strong></p>
        <p class="lp-small">
            Tambahkan karya pertama Anda. Produk terbit akan langsung bisa dibeli dan ditemukan lewat pencarian.
        </p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('seller_product_create') ?>">Tambah produk</a>
    </div>
<?php else: ?>
    <div class="lp-table-wrap">
        <table class="lp-table">
            <caption class="lp-visually-hidden">Daftar produk milik Anda</caption>
            <thead>
                <tr>
                    <th scope="col">Produk</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Stok</th>
                    <th scope="col">Terbit</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="lp-visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <div class="lp-row" style="gap:.6rem;">
                                <?php $cover = $product['cover_path'] ?? null; ?>
                                <?php if ($cover !== null && $cover !== ''): ?>
                                    <img src="<?= esc((string) url_gambar($cover)) ?>" alt="">
                                <?php endif; ?>
                                <span>
                                    <a href="<?= route_to('seller_product_edit', (string) $product['id']) ?>">
                                        <strong><?= esc((string) $product['name']) ?></strong>
                                    </a>
                                    <br>
                                    <span class="lp-small lp-muted">
                                        <?= esc((string) $product['product_code']) ?>
                                        &middot; diubah <?= waktu_smart($product['updated_at']) ?>
                                    </span>
                                </span>
                            </div>
                        </td>
                        <td><?= esc((string) ($product['category_name'] ?? '—')) ?></td>
                        <td class="lp-num"><?= rupiah($product['price']) ?></td>
                        <td class="lp-num">
                            <?php if ($product['made_to_order']): ?>
                                <span class="lp-tag lp-tag--made">Khusus</span>
                            <?php elseif ((int) $product['stock'] <= 0): ?>
                                <span class="lp-tag lp-tag--out">Habis</span>
                            <?php elseif ((int) $product['stock'] <= (int) $product['low_stock_threshold']): ?>
                                <span class="lp-tag lp-tag--stock"><?= esc((string) $product['stock']) ?></span>
                            <?php else: ?>
                                <?= esc((string) $product['stock']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="lp-num"><?= esc((string) $product['sold_count']) ?></td>
                        <td>
                            <?php if ($product['status'] === 'published'): ?>
                                <span class="lp-pill lp-pill--ok">Terbit</span>
                            <?php elseif ($product['status'] === 'archived'): ?>
                                <span class="lp-pill lp-pill--bad">Arsip</span>
                            <?php else: ?>
                                <span class="lp-pill lp-pill--warn">Draf</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="lp-row" style="gap:.35rem;flex-wrap:wrap;">
                                <a class="lp-btn lp-btn--sm lp-btn--outline"
                                   href="<?= route_to('seller_product_edit', (string) $product['id']) ?>">
                                    Ubah
                                </a>

                                <?php if ($product['status'] === 'published' && ! empty($product['shop_slug'])): ?>
                                    <a class="lp-btn lp-btn--sm lp-btn--ghost"
                                       href="<?= route_to('storefront', (string) $product['shop_slug']) ?>">
                                        Lihat
                                    </a>
                                <?php endif; ?>

                                <form method="post" action="<?= route_to('seller_product_publish', (string) $product['id']) ?>">
                                    <?= csrf_field() ?>
                                    <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">
                                        <?= $product['status'] === 'published' ? 'Tarik' : 'Terbitkan' ?>
                                    </button>
                                </form>

                                <form method="post"
                                      action="<?= route_to('seller_product_delete', (string) $product['id']) ?>"
                                      data-confirm="Hapus produk ini? Tindakan tidak dapat dibatalkan.">
                                    <?= csrf_field() ?>
                                    <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total > 20): ?>
        <nav class="lp-pagination" aria-label="Halaman produk">
            <?php if ($page > 1): ?>
                <a href="?<?= esc(http_build_query(['page' => $page - 1])) ?>">Sebelumnya</a>
            <?php endif; ?>

            <span>Halaman <?= esc((string) $page) ?> dari <?= esc((string) (int) ceil($total / 20)) ?></span>

            <?php if ($page * 20 < $total): ?>
                <a href="?<?= esc(http_build_query(['page' => $page + 1])) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
