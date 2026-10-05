<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb"><a href="<?= site_url() ?>">Beranda</a> &rsaquo; Akun Saya</p>

<h1>Akun Saya</h1>


<div class="lp-stats">
    <div class="lp-panel lp-stats">
        <div>
            <span class="lp-small lp-muted">Pesanan aktif</span>
            <strong><?= esc((string) $dashboard['active_order_count']) ?></strong>
        </div>
        <div>
            <span class="lp-small lp-muted">Total belanja</span>
            <strong><?= esc(rupiah($dashboard['contribution_total'])) ?></strong>
        </div>
        <div>
            <span class="lp-small lp-muted">Pesanan</span>
            <strong><?= esc((string) $dashboard['orders_total']) ?></strong>
        </div>
    </div>
</div>

<div class="lp-row" style="margin:1.5rem 0;gap:.5rem;flex-wrap:wrap;">
    <a class="lp-btn lp-btn--outline lp-btn--sm" href="<?= route_to('orders') ?>">Pesanan saya</a>
    <a class="lp-btn lp-btn--outline lp-btn--sm" href="<?= route_to('addresses') ?>">Alamat pengiriman</a>
    <a class="lp-btn lp-btn--outline lp-btn--sm" href="<?= route_to('chat_index') ?>">
        Pesan <?php if (! empty($dashboard['unread_chat'])): ?>
            <span class="lp-badge"><?= esc((string) $dashboard['unread_chat']) ?></span>
        <?php endif; ?>
    </a>
    <?php if ($is_seller): ?>
        <a class="lp-btn lp-btn--solid lp-btn--sm" href="<?= route_to('seller_dashboard') ?>">Buka Mitra</a>
    <?php endif; ?>
</div>

<div class="lp-split">
    <div>
        <div class="lp-panel">
            <div class="lp-panel__head">
                <h2>Profil saya</h2>
            </div>

            <form method="post" action="<?= route_to('profile_update') ?>">
                <?= csrf_field() ?>

                <div class="lp-form__row">
                    <div class="lp-field">
                        <label for="full_name">Nama lengkap</label>
                        <input id="full_name" type="text" name="full_name" maxlength="150" required
                               value="<?= esc((string) ($dashboard['profile']['full_name'] ?? '')) ?>">
                    </div>

                    <div class="lp-field">
                        <label for="nickname">Nama panggilan</label>
                        <input id="nickname" type="text" name="nickname" maxlength="60"
                               value="<?= esc((string) ($dashboard['profile']['nickname'] ?? '')) ?>">
                    </div>
                </div>

                <div class="lp-form__row">
                    <div class="lp-field">
                        <label for="birth_date">Tanggal lahir</label>
                        <input id="birth_date" type="date" name="birth_date"
                               value="<?= esc((string) ($dashboard['profile']['birth_date'] ?? '')) ?>">
                    </div>

                    <div class="lp-field">
                        <label for="gender">Jenis kelamin</label>
                        <select id="gender" name="gender">
                            <option value="">Tidak diisi</option>
                            <option value="pria" <?= ($dashboard['profile']['gender'] ?? '') === 'pria' ? 'selected' : '' ?>>Pria</option>
                            <option value="wanita" <?= ($dashboard['profile']['gender'] ?? '') === 'wanita' ? 'selected' : '' ?>>Wanita</option>
                        </select>
                    </div>
                </div>

                <div class="lp-field">
                    <label for="bio">Tentang saya</label>
                    <textarea id="bio" name="bio" rows="4" maxlength="500"
                              placeholder="Ceritakan sedikit tentang diri Anda."><?= esc((string) ($dashboard['profile']['bio'] ?? '')) ?></textarea>
                </div>

                <button class="lp-btn lp-btn--solid" type="submit">Simpan profil</button>
            </form>
        </div>

        <div class="lp-panel">
            <div class="lp-panel__head">
                <h2>Pesanan terakhir</h2>
                <a class="lp-more" href="<?= route_to('orders') ?>">Lihat semua &rarr;</a>
            </div>

            <?php if ($dashboard['recent_orders'] === []): ?>
                <p class="lp-muted lp-small">Belum ada pesanan.</p>
            <?php else: ?>
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Nomor</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col" class="lp-num">Total</th>
                                <th scope="col"><span class="lp-visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard['recent_orders'] as $order): ?>
                                <tr>
                                    <td>
                                        <?= esc((string) $order['order_number']) ?>
                                        <?php if (! empty($order['shop_name'])): ?>
                                            <br><small class="lp-muted"><?= esc((string) $order['shop_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc(format_tanggal((string) $order['placed_at'])) ?></td>
                                    <td class="lp-num"><?= esc(rupiah($order['grand_total'])) ?></td>
                                    <td>
                                        <a class="lp-btn lp-btn--sm lp-btn--ghost"
                                           href="<?= route_to('order_show', (int) $order['id']) ?>">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <aside class="lp-aside-sticky">
        <?php if ($is_seller && $dashboard['seller_profile'] !== null): ?>
            <div class="lp-panel">
                <h2>Sanggar saya</h2>
                <p>
                    <strong><?= esc((string) $dashboard['seller_profile']['display_name']) ?></strong>
                    <?php if (! empty($dashboard['seller_profile']['slug'])): ?>
                        <br>
                        <a class="lp-small" href="<?= route_to('storefront', (string) $dashboard['seller_profile']['slug']) ?>">
                            Lihat halaman toko &rarr;
                        </a>
                    <?php endif; ?>
                </p>

                <?php if ($dashboard['seller_profile']['rating_count'] > 0): ?>
                    <?= bintang($dashboard['seller_profile']['rating_average'], $dashboard['seller_profile']['rating_count']) ?>
                <?php endif; ?>

                <a class="lp-btn lp-btn--outline lp-btn--block" style="margin-top:1rem;"
                   href="<?= route_to('seller_dashboard') ?>">Buka dasbor Mitra</a>
            </div>
        <?php endif; ?>

        <div class="lp-panel">
            <h2>Alamat tersimpan</h2>

            <?php if ($dashboard['addresses'] === []): ?>
                <p class="lp-muted lp-small">Belum ada alamat.</p>
                <a class="lp-btn lp-btn--outline lp-btn--block" href="<?= route_to('address_create') ?>">
                    Tambah alamat
                </a>
            <?php else: ?>
                <?php foreach ($dashboard['addresses'] as $address): ?>
                    <div style="margin-bottom:.75rem;">
                        <strong><?= esc((string) $address['label']) ?></strong>
                        <?php if ($address['is_default']): ?>
                            <span class="lp-chip">Utama</span>
                        <?php endif; ?>
                        <br>
                        <small class="lp-muted">
                            <?= esc((string) $address['address_line']) ?>,
                            <?= esc(lokasi_teks($address)) ?>
                        </small>
                    </div>
                <?php endforeach; ?>
                <a class="lp-btn lp-btn--ghost lp-btn--block" href="<?= route_to('addresses') ?>">
                    Kelola alamat
                </a>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
