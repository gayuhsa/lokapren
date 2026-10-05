<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo; Keranjang
</p>

<h1>Keranjang Belanja</h1>

<?php if ($cart['groups'] === []): ?>
    <div class="lp-empty">
        <p><strong>Keranjang Anda masih kosong.</strong></p>
        <p class="lp-small">Mulai dari katalog dan temukan karya yang cocok.</p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('catalog') ?>">Jelajahi Katalog</a>
    </div>
<?php else: ?>
    <div class="lp-split">
        <form method="post" action="<?= route_to('cart_update') ?>">
            <?= csrf_field() ?>

            <?php foreach ($cart['groups'] as $group): ?>
                <div class="lp-panel">
                    <div class="lp-panel__head">
                        <div>
                            <h2><?= esc((string) $group['shop_name']) ?></h2>
                            <small class="lp-muted">
                                <?= esc((string) ($group['shop_place'] ?? '')) ?>
                            </small>
                        </div>
                        <a class="lp-more" href="<?= route_to('storefront', (string) $group['shop_slug']) ?>">
                            Lihat toko &rarr;
                        </a>
                    </div>

                    <div class="lp-table-wrap">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Karya</th>
                                    <th scope="col" class="lp-num">Harga</th>
                                    <th scope="col" class="lp-num">Jumlah</th>
                                    <th scope="col" class="lp-num">Subtotal</th>
                                    <th scope="col"><span class="lp-visually-hidden">Hapus</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['lines'] as $line): ?>
                                    <tr>
                                        <td>
                                            <div class="lp-row" style="flex-wrap:nowrap;align-items:flex-start;">
                                                <?php if (! empty($line['cover_path'])): ?>
                                                    <img src="<?= esc((string) url_gambar($line['cover_path'])) ?>"
                                                         alt="" width="56" height="56">
                                                <?php endif; ?>
                                                <div>
                                                    <a href="<?= route_to('product', (string) $line['slug']) ?>">
                                                        <?= esc((string) $line['product_name']) ?>
                                                    </a>
                                                    <?php if (! empty($line['variant_label'])): ?>
                                                        <br><small class="lp-muted">
                                                            <?= esc((string) $line['variant_label']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if (! empty($line['note'])): ?>
                                                        <br><small class="lp-muted">
                                                            Catatan: <?= esc((string) $line['note']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="lp-num"><?= esc(rupiah($line['unit_price'])) ?></td>
                                        <td class="lp-num">
                                            <span class="lp-row" style="flex-wrap:nowrap;justify-content:flex-end;gap:.3rem;"
                                                  data-qty-stepper>
                                                <button class="lp-btn lp-btn--outline lp-btn--sm" type="button"
                                                        data-step="-1" aria-label="Kurangi satu">&minus;</button>
                                                <input type="number"
                                                       name="quantities[<?= esc((string) $line['id']) ?>]"
                                                       value="<?= esc((string) $line['quantity']) ?>"
                                                       min="0"
                                                       max="<?= esc((string) ($line['made_to_order'] ? 99 : max(1, (int) $line['stock']))) ?>"
                                                       style="width:4.5rem;text-align:center;">
                                                <button class="lp-btn lp-btn--outline lp-btn--sm" type="button"
                                                        data-step="1" aria-label="Tambah satu">+</button>
                                            </span>
                                        </td>
                                        <td class="lp-num"><?= esc(rupiah($line['subtotal'])) ?></td>
                                        <td>
                                            <button class="lp-btn lp-btn--ghost lp-btn--sm" type="submit"
                                                    form="hapus-<?= esc((string) $line['id']) ?>"
                                                    aria-label="Hapus dari keranjang">Hapus</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="lp-row">
                <button class="lp-btn lp-btn--solid" type="submit">Perbarui Keranjang</button>
                <a class="lp-btn lp-btn--ghost" href="<?= route_to('catalog') ?>">Lanjut Belanja</a>
            </div>
            <p class="lp-small lp-muted" style="margin-top:.5rem;">
                Isi jumlah dengan <strong>0</strong> untuk mengeluarkan karya dari keranjang.
            </p>
        </form>

        <aside class="lp-aside-sticky">
            <div class="lp-panel">
                <h2>Ringkasan</h2>

                <div class="lp-totals">
                    <div class="lp-totals__row">
                        <span>Total karya</span>
                        <span><?= esc((string) $cart['item_count']) ?></span>
                    </div>
                    <div class="lp-totals__row">
                        <span>Jumlah sanggar</span>
                        <span><?= esc((string) count($cart['groups'])) ?></span>
                    </div>
                    <div class="lp-totals__row lp-totals__row--grand">
                        <span>Subtotal</span>
                        <span><?= esc(rupiah($cart['subtotal'])) ?></span>
                    </div>
                </div>

                <a class="lp-btn lp-btn--solid lp-btn--block" href="<?= route_to('checkout_index') ?>"
                   style="margin-top:1rem;">
                    Lanjut ke Checkout
                </a>

                <?php if (count($cart['groups']) > 1): ?>
                    <p class="lp-small lp-muted" style="margin-top:.6rem;">
                        Keranjang ini berisi <?= esc((string) count($cart['groups'])) ?> sanggar.
                        Saat checkout, satu pesanan dibuat untuk masing-masing sanggar.
                    </p>
                <?php endif; ?>

                <form method="post" action="<?= route_to('cart_clear') ?>" style="margin-top:.5rem;">
                    <?= csrf_field() ?>
                    <button class="lp-btn lp-btn--ghost lp-btn--block" type="submit"
                            data-confirm="Kosongkan seluruh keranjang?">
                        Kosongkan Keranjang
                    </button>
                </form>
            </div>
        </aside>
    </div>

    <?php
    // Remove buttons live outside the quantity form because each one posts to a
    // different route, so they are declared as standalone forms here.
    foreach ($cart['groups'] as $group) {
        foreach ($group['lines'] as $line) {
            ?>
            <form id="hapus-<?= esc((string) $line['id']) ?>"
                  method="post" action="<?= route_to('cart_remove', (int) $line['id']) ?>" hidden>
                <?= csrf_field() ?>
            </form>
            <?php
        }
    }
    ?>
<?php endif; ?>

<?= $this->endSection() ?>
