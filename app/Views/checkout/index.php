<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo;
    <a href="<?= route_to('cart') ?>">Keranjang</a> &rsaquo; Checkout
</p>

<h1>Checkout</h1>

<?php if ($summary['dropped'] !== []): ?>
    <div class="lp-alert lp-alert--error">
        <strong>Sebagian item tidak tersedia lagi.</strong>
        <?php foreach ($summary['dropped'] as $drop): ?>
            <div><?= esc((string) $drop) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($addresses === []): ?>
    <div class="lp-empty">
        <p><strong>Alamat pengiriman belum ada.</strong></p>
        <p class="lp-small">Tambahkan satu alamat agar pesanan bisa dikirim.</p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('address_create') ?>">Tambah Alamat</a>
    </div>
<?php else: ?>
    <?php
    // Preselect the default address, or the one the buyer already picked.
    $defaultAddress = null;
    foreach ($addresses as $address) {
        if ((int) $address['id'] === $selected) {
            $defaultAddress = $address;
        }
    }
    $defaultAddress ??= $addresses[0];
    ?>

    <form method="post" action="<?= route_to('checkout_store') ?>">
        <?= csrf_field() ?>

        <div class="lp-split">
            <div>
                <div class="lp-panel">
                    <h2>Alamat pengiriman</h2>

                    <?php foreach ($addresses as $address): ?>
                        <label class="lp-radio-card">
                            <input type="radio" name="address_id" value="<?= esc((string) $address['id']) ?>"
                                   <?= (int) $address['id'] === (int) $defaultAddress['id'] ? 'checked' : '' ?>>
                            <span>
                                <strong>
                                    <?= esc((string) $address['label']) ?>
                                    <?php if ($address['is_hotel']): ?>
                                        <span class="lp-chip">Hotel</span>
                                    <?php endif; ?>
                                </strong><br>
                                <?= esc((string) $address['recipient_name']) ?>
                                &middot; <?= esc((string) $address['recipient_phone']) ?><br>
                                <?= esc((string) $address['address_line']) ?><br>
                                <span class="lp-muted">
                                    <?= esc(lokasi_teks($address)) ?>
                                    <?= $address['postal_code'] !== null && $address['postal_code'] !== ''
                                        ? esc((string) $address['postal_code']) : '' ?>
                                </span>
                                <?php if (! empty($address['landmark'])): ?>
                                    <br><span class="lp-muted">Patokan: <?= esc((string) $address['landmark']) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>

                    <p class="lp-small" style="margin-top:.75rem;">
                        <a href="<?= route_to('address_create') ?>">+ Tambah alamat lain</a>
                    </p>
                </div>

                <div class="lp-panel">
                    <h2>Pengiriman</h2>

                    <?php
                    $currentCode = (string) $summary['shipping_option']['code'];
                    $current     = $summary['shipping_option'];
                    ?>

                    <?php foreach ($shipping_options as $code => $option): ?>
                        <label class="lp-radio-card">
                            <input type="radio" name="shipping_option" value="<?= esc($code) ?>"
                                   data-total="<?= esc((string) ($summary['subtotal'] + (int) $option['fee'] * max(1, $summary['seller_count']))) ?>"
                                   <?= $code === $currentCode ? 'checked' : '' ?>
                                   data-recalc>
                            <span>
                                <strong><?= esc((string) $option['label']) ?></strong>
                                &mdash; <?= esc(rupiah((int) $option['fee'])) ?><br>
                                <span class="lp-muted">
                                    <?= esc((string) $option['description']) ?><br>
                                    Estimasi <?= esc((string) $option['eta']) ?>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>

                    <p class="lp-small lp-muted" style="margin-top:.75rem;">
                        Ongkos kirim dihitung <?= esc((string) $summary['seller_count']) ?>x karena setiap
                        sanggar mengirim dari workshop-nya sendiri.
                    </p>
                </div>

                <div class="lp-panel">
                    <h2>Catatan untuk pengrajin</h2>
                    <div class="lp-field">
                        <label for="customer_note">Catatan (opsional)</label>
                        <textarea id="customer_note" name="customer_note" rows="4" maxlength="500"
                                  placeholder="Misalnya: mohon dikemas tanpa kardus, warna kopi."></textarea>
                    </div>
                </div>
            </div>

            <aside class="lp-aside-sticky">
                <div class="lp-panel">
                    <h2>Ringkasan pesanan</h2>

                    <?php foreach ($summary['groups'] as $index => $group): ?>
                        <div class="lp-stack" style="margin-bottom:.75rem;">
                            <strong>
                                Pesanan <?= esc((string) ($index + 1)) ?> &mdash;
                                <?= esc(rupiah($group['subtotal'])) ?>
                            </strong>
                            <?php foreach ($group['lines'] as $line): ?>
                                <div class="lp-row" style="justify-content:space-between;gap:.5rem;">
                                    <span class="lp-small">
                                        <?= esc((string) $line['quantity']) ?>&times;
                                        <?= esc((string) $line['product_name']) ?>
                                        <?php if (! empty($line['variant_label'])): ?>
                                            <span class="lp-muted">(<?= esc((string) $line['variant_label']) ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="lp-small lp-num"><?= esc(rupiah($line['subtotal'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="lp-row lp-small" style="justify-content:space-between;">
                                <span class="lp-muted">Ongkos kirim</span>
                                <span><?= esc(rupiah($group['shipping_fee'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="lp-totals">
                        <div class="lp-totals__row">
                            <span>Subtotal (<?= esc((string) $summary['item_count']) ?> item)</span>
                            <span><?= esc(rupiah($summary['subtotal'])) ?></span>
                        </div>
                        <div class="lp-totals__row">
                            <span>Ongkos kirim</span>
                            <span><?= esc(rupiah($summary['shipping_total'])) ?></span>
                        </div>
                        <div class="lp-totals__row lp-totals__row--grand" data-grand-total>
                            <span>Total</span>
                            <span><?= esc(rupiah($summary['grand_total'])) ?></span>
                        </div>
                    </div>

                    <button class="lp-btn lp-btn--solid lp-btn--block" type="submit" style="margin-top:1rem;"
                            data-confirm="Buat pesanan untuk <?= esc((string) $summary['seller_count']) ?> sanggar?">
                        Buat Pesanan
                    </button>

                    <p class="lp-small lp-muted" style="margin-top:.6rem;">
                        Belum ada pembayaran gateways yang terhubung. Setelah pesanan dibuat,
                        seller mengonfirmasi pembayaran lewat transfer.
                    </p>
                </div>
            </aside>
        </div>
    </form>
<?php endif; ?>

<?= $this->endSection() ?>
