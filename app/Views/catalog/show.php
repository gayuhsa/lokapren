<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url() ?>">Beranda</a> &rsaquo;
    <a href="<?= route_to('catalog') ?>">Katalog</a> &rsaquo;
    <?= esc((string) $product['name']) ?>
</p>

<div class="lp-split">
    <div>
        <div class="lp-panel">
            <?php if ($product['images'] !== []): ?>
                <img src="<?= esc((string) url_gambar($product['images'][0]['file_path'])) ?>"
                     alt="<?= esc((string) $product['name']) ?>"
                     width="800" height="600"
                     style="border-radius:var(--lp-radius-lg);aspect-ratio:4/3;object-fit:cover;width:100%;">
            <?php else: ?>
                <div class="lp-card__media" style="border-radius:var(--lp-radius-lg);">
                    <span class="lp-card__placeholder">Tanpa foto</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="lp-panel">
            <h2>Tentang karya ini</h2>

            <?php if (! empty($product['story'])): ?>
                <h3>Cerita di balik karya</h3>
                <p><?= nl2br(esc((string) $product['story'])) ?></p>
            <?php endif; ?>

            <?php if (! empty($product['description'])): ?>
                <h3>Detail</h3>
                <p><?= nl2br(esc((string) $product['description'])) ?></p>
            <?php endif; ?>

            <table class="lp-table">
                <tbody>
                    <?php if (! empty($product['material'])): ?>
                        <tr><th scope="row">Bahan</th><td><?= esc((string) $product['material']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (! empty($product['finishing'])): ?>
                        <tr><th scope="row">Finishing</th><td><?= esc((string) $product['finishing']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (! empty($product['weight_gram'])): ?>
                        <tr><th scope="row">Berat</th><td><?= esc((string) $product['weight_gram']) ?> gram</td></tr>
                    <?php endif; ?>
                    <?php if (! empty($product['production_days'])): ?>
                        <tr><th scope="row">Estimasi pengerjaan</th>
                            <td><?= esc((string) $product['production_days']) ?> hari kerja</td></tr>
                    <?php endif; ?>
                    <tr><th scope="row">Terjual</th><td><?= esc((string) $product['sold_count']) ?> karya</td></tr>
                </tbody>
            </table>
        </div>

        <div class="lp-panel">
            <div class="lp-panel__head">
                <h2>Ulasan Pembeli</h2>
                <?php if ($product['review_total'] > 0): ?>
                    <?= bintang($product['rating_average'], $product['review_total']) ?>
                <?php endif; ?>
            </div>

            <?php if ($product['reviews'] === []): ?>
                <p class="lp-muted lp-small">Belum ada ulasan untuk karya ini.</p>
            <?php else: ?>
                <?php if ($product['review_total'] > 0): ?>
                    <table class="lp-table" style="max-width:20rem;margin-bottom:1rem;">
                        <tbody>
                            <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                                <?php $count = (int) ($product['breakdown'][$star] ?? 0); ?>
                                <tr>
                                    <th scope="row" style="width:3rem;"><?= esc((string) $star) ?> ★</th>
                                    <td>
                                        <div class="lp-row" style="gap:.4rem;">
                                            <div style="flex:1;background:var(--lp-sand-dark);border-radius:999px;height:.5rem;">
                                                <div style="width:<?= esc((string) ($product['review_total'] > 0 ? round($count / $product['review_total'] * 100) : 0)) ?>%;
                                                            background:var(--lp-clay);border-radius:999px;height:100%;"></div>
                                            </div>
                                            <small class="lp-muted"><?= esc((string) $count) ?></small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <ul class="lp-stack" style="list-style:none;padding:0;margin:0;">
                    <?php foreach ($product['reviews'] as $review): ?>
                        <li>
                    <strong>
                        <?= esc((string) ($review['customer_full_name'] ?? $review['customer_name'] ?? 'Pembeli')) ?>
                    </strong>
                            <?= bintang($review['rating'], null, false) ?>
                            <p class="lp-small"><?= esc((string) ($review['body'] ?? '')) ?></p>
                            <small class="lp-muted"><?= esc(waktu_smart($review['created_at'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <aside class="lp-aside-sticky">
        <div class="lp-panel">
            <h1><?= esc((string) $product['name']) ?></h1>

            <?php if (! empty($product['subtitle'])): ?>
                <p class="lp-muted"><?= esc((string) $product['subtitle']) ?></p>
            <?php endif; ?>

            <p class="lp-price" style="font-size:1.4rem;"><?= esc(rupiah($product['price'])) ?></p>

            <?php if ($product['shop_slug'] !== null): ?>
                <div class="lp-row" style="margin:.75rem 0;">
                    <span class="lp-avatar" aria-hidden="true">
                        <?= esc(inisial((string) $product['shop_name'])) ?>
                    </span>
                    <div>
                        <a href="<?= route_to('storefront', (string) $product['shop_slug']) ?>">
                            <?= esc((string) $product['shop_name']) ?>
                        </a>
                        <br>
                        <small class="lp-muted">
                            <?php if (! empty($product['shop_village'])): ?>
                                <?= esc(lokasi_teks([
                                    'village' => $product['shop_village'],
                                    'regency' => $product['shop_regency'],
                                ])) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= route_to('cart_add', (int) $product['id']) ?>">
                <?= csrf_field() ?>

                <?php if ($product['variants'] !== []): ?>
                    <div class="lp-field">
                        <label for="variant">Pilihan ukuran</label>
                        <select id="variant" name="variant_id" required>
                            <?php foreach ($product['variants'] as $variant): ?>
                                <option value="<?= esc((string) $variant['id']) ?>">
                                    <?= esc((string) $variant['label']) ?>
                                    — <?= esc(rupiah($variant['price'] ?? $product['price'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="lp-field">
                    <label for="quantity">Jumlah</label>
                    <input id="quantity" type="number" name="quantity" value="1" min="1"
                           max="<?= esc((string) ($product['made_to_order'] ? 99 : max(1, (int) $product['stock']))) ?>">
                </div>

                <div class="lp-field">
                    <label for="note">Catatan untuk pengrajin <span class="lp-muted">(opsional)</span></label>
                    <textarea id="note" name="note" rows="3" maxlength="255"
                              placeholder="Misalnya: warna natural, tanpa finishing glossy."></textarea>
                </div>

                <button class="lp-btn lp-btn--solid lp-btn--block" type="submit"
                        <?= $product['made_to_order'] || (int) $product['stock'] > 0 ? '' : 'disabled' ?>>
                    <?= $product['made_to_order'] || (int) $product['stock'] > 0 ? 'Tambah ke Keranjang' : 'Stok Habis' ?>
                </button>
            </form>

            <?php if (auth()->loggedIn()): ?>
                <form method="post" action="<?= route_to('chat_start', (int) $product['id']) ?>"
                      style="margin-top:.5rem;">
                    <?= csrf_field() ?>
                    <button class="lp-btn lp-btn--outline lp-btn--block" type="submit">
                        Tanya Pengrajin
                    </button>
                </form>
            <?php else: ?>
                <p class="lp-small lp-muted" style="margin-top:.75rem;">
                    <a href="<?= route_to('login') ?>">Masuk</a> untuk bertanya langsung ke pengrajin.
                </p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
