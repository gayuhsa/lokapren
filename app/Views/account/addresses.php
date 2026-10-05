<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('profile') ?>">Akun</a> &rsaquo; Alamat
</p>

<div class="lp-section__head">
    <div>
        <h1>Alamat Pengiriman</h1>
        <p class="lp-muted">Alamat ini dipakai saat checkout dan disimpan pada tiap pesanan.</p>
    </div>
    <a class="lp-btn lp-btn--solid lp-btn--sm" href="<?= route_to('address_create') ?>">+ Tambah alamat</a>
</div>


<?php if ($addresses === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada alamat tersimpan.</strong></p>
        <p class="lp-small">Tambahkan alamat agar proses checkout lebih cepat.</p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('address_create') ?>">Tambah alamat pertama</a>
    </div>
<?php else: ?>
    <div class="lp-grid lp-grid--two">
        <?php foreach ($addresses as $address): ?>
            <div class="lp-card">
                <div class="lp-card__body">
                    <div class="lp-panel__head">
                        <strong><?= esc((string) $address['label']) ?></strong>
                        <?php if ($address['is_default']): ?>
                            <span class="lp-chip">Alamat utama</span>
                        <?php endif; ?>
                    </div>

                    <p class="lp-small">
                        <strong><?= esc((string) $address['recipient_name']) ?></strong>
                        &middot; <?= esc((string) $address['recipient_phone']) ?><br>
                        <?= esc((string) $address['address_line']) ?><br>
                        <span class="lp-muted">
                            <?= esc(lokasi_teks($address)) ?>
                            <?= $address['postal_code'] !== null && $address['postal_code'] !== ''
                                ? esc((string) $address['postal_code']) : '' ?>
                        </span>
                        <?php if ($address['is_hotel']): ?>
                            <span class="lp-chip">Hotel</span>
                        <?php endif; ?>
                        <?php if (! empty($address['landmark'])): ?>
                            <br><span class="lp-muted">Patokan: <?= esc((string) $address['landmark']) ?></span>
                        <?php endif; ?>
                        <?php if (! empty($address['delivery_notes'])): ?>
                            <br><span class="lp-muted">Catatan: <?= esc((string) $address['delivery_notes']) ?></span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="lp-card__foot">
                    <a class="lp-btn lp-btn--sm lp-btn--outline"
                       href="<?= route_to('address_edit', (int) $address['id']) ?>">Ubah</a>

                    <?php if (! $address['is_default']): ?>
                        <form method="post" action="<?= route_to('address_default', (int) $address['id']) ?>">
                            <?= csrf_field() ?>
                            <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Jadikan utama</button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="<?= route_to('address_delete', (int) $address['id']) ?>"
                          data-confirm="Hapus alamat ini?">
                        <?= csrf_field() ?>
                        <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
