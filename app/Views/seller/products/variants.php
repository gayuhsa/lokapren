<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $productId = (int) $product['id']; ?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a> &rsaquo;
    <a href="<?= route_to('seller_products') ?>">Produk saya</a> &rsaquo;
    <a href="<?= route_to('seller_product_edit', $productId) ?>"><?= esc((string) $product['name']) ?></a> &rsaquo;
    Pilihan ukuran
</p>

<h1>Pilihan Ukuran</h1>

<p class="lp-muted">
    Untuk <strong><?= esc((string) $product['name']) ?></strong>
    &mdash; harga dasar <?= esc(rupiah($product['price'])) ?>.
</p>

<div class="lp-split">
    <div>
        <div class="lp-panel">
            <h2>Daftar pilihan</h2>

            <?php if ($product['variants'] === []): ?>
                <p class="lp-muted">
                    Belum ada pilihan ukuran. Produk ini akan selalu dibeli tanpa pilihan tambahan.
                </p>
            <?php else: ?>
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Label</th>
                                <th scope="col">SKU</th>
                                <th scope="col" class="lp-num">Harga</th>
                                <th scope="col" class="lp-num">Stok</th>
                                <th scope="col" class="lp-num">Berat</th>
                                <th scope="col"><span class="lp-visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product['variants'] as $variant): ?>
                                <tr>
                                    <td>
                                        <strong><?= esc((string) $variant['label']) ?></strong>
                                        <?php if ((int) ($variant['is_default'] ?? 0) === 1): ?>
                                            <span class="lp-pill lp-pill--ok">Bawaan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="lp-small lp-muted"><?= esc((string) ($variant['sku'] ?? '—')) ?></td>
                                    <td class="lp-num">
                                        <?php if ($variant['price'] === null): ?>
                                            <span class="lp-muted">Ikuti produk</span>
                                        <?php else: ?>
                                            <?= esc(rupiah($variant['price'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="lp-num"><?= esc((string) $variant['stock']) ?></td>
                                    <td class="lp-num">
                                        <?= $variant['weight_gram'] === null
                                            ? '<span class="lp-muted">—</span>'
                                            : esc((string) $variant['weight_gram']) . ' g' ?>
                                    </td>
                                    <td>
                                        <form method="post"
                                              action="<?= route_to('seller_variant_delete', $productId, (int) $variant['id']) ?>"
                                              data-confirm="Hapus pilihan ukuran ini?">
                                            <?= csrf_field() ?>
                                            <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="lp-panel">
            <h2>Tambah pilihan</h2>

            <form method="post" action="<?= route_to('seller_variant_store', $productId) ?>" class="lp-form">
                <?= csrf_field() ?>

                <div class="lp-field">
                    <label for="label">Label</label>
                    <input id="label" name="label" required maxlength="100"
                           value="<?= esc(old('label')) ?>"
                           placeholder="Contoh: 20 cm, Ukuran kecil">
                </div>

                <div class="lp-field">
                    <label for="sku">Kode barang</label>
                    <input id="sku" name="sku" maxlength="64" value="<?= esc(old('sku')) ?>">
                    <small class="lp-small lp-muted">Boleh dikosongkan.</small>
                </div>

                <div class="lp-field">
                    <label for="price">Harga (Rp)</label>
                    <input id="price" name="price" type="number" inputmode="numeric" min="0"
                           value="<?= esc(old('price')) ?>">
                    <small class="lp-small lp-muted">
                        Kosongkan untuk memakai harga produk: <?= esc(rupiah($product['price'])) ?>.
                    </small>
                </div>

                <div class="lp-form__row">
                    <div class="lp-field">
                        <label for="stock">Stok</label>
                        <input id="stock" name="stock" type="number" inputmode="numeric" min="0"
                               value="<?= esc(old('stock', '0')) ?>">
                    </div>

                    <div class="lp-field">
                        <label for="weight_gram">Berat (gram)</label>
                        <input id="weight_gram" name="weight_gram" type="number" min="0"
                               value="<?= esc(old('weight_gram')) ?>">
                    </div>
                </div>

                <div class="lp-field">
                    <label class="lp-field--inline">
                        <input type="checkbox" name="is_default" value="1"
                            <?= old('is_default') === '1' ? 'checked' : '' ?>>
                        Jadikan pilihan bawaan
                    </label>
                </div>

                <button class="lp-btn lp-btn--solid lp-btn--block" type="submit">Simpan pilihan</button>
            </form>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
