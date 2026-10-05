<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$editing = $product !== null;
$value   = static fn (string $key, mixed $default = ''): string => (string) old($key, $editing ? ($product[$key] ?? $default) : $default);
?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a> &rsaquo;
    <a href="<?= route_to('seller_products') ?>">Produk saya</a> &rsaquo;
    <?= $editing ? 'Ubah produk' : 'Tambah produk' ?>
</p>

<h1><?= $editing ? 'Ubah Produk' : 'Tambah Produk' ?></h1>

<?php if ($editing): ?>
    <p class="lp-muted">
        Kode produk <strong><?= esc((string) $product['product_code']) ?></strong>
        dibuat otomatis dan tidak bisa diubah.
    </p>
<?php else: ?>
    <p class="lp-muted">Harga diisi dalam rupiah penuh tanpa titik atau koma.</p>
<?php endif; ?>

<form method="post" action="<?= esc((string) $action) ?>" class="lp-form">
    <?= csrf_field() ?>

    <fieldset style="border:0;padding:0;margin:0 0 1.5rem;">
        <legend class="lp-field__label" style="margin-bottom:.75rem;">Informasi dasar</legend>

        <div class="lp-field">
            <label for="name">Nama produk</label>
            <input id="name" name="name" required maxlength="180"
                   value="<?= esc($value('name')) ?>">
        </div>

        <div class="lp-field">
            <label for="subtitle">Subjudul singkat</label>
            <input id="subtitle" name="subtitle" maxlength="255"
                   value="<?= esc($value('subtitle')) ?>">
            <small class="lp-small lp-muted">Satu kalimat yang muncul di kartu katalog.</small>
        </div>

        <div class="lp-field">
            <label for="summary">Ringkasan</label>
            <textarea id="summary" name="summary" rows="2" maxlength="500"><?= esc($value('summary')) ?></textarea>
        </div>

        <div class="lp-field">
            <label for="description">Deskripsi lengkap</label>
            <textarea id="description" name="description" rows="6" maxlength="5000"><?= esc($value('description')) ?></textarea>
            <small class="lp-small lp-muted">
                Tulis seperti biasa: bahan, proses, dan motif yang membuat karya ini berbeda.
                Baris kosong di antara paragraf akan dipertahankan saat ditampilkan.
            </small>
        </div>

        <div class="lp-field">
            <label for="story">Cerita di balik karya</label>
            <textarea id="story" name="story" rows="5" maxlength="5000"><?= esc($value('story')) ?></textarea>
        </div>
    </fieldset>

    <fieldset style="border:0;padding:0;margin:0 0 1.5rem;">
        <legend class="lp-field__label" style="margin-bottom:.75rem;">Kategori dan bahan</legend>

        <div class="lp-field">
            <label for="category_id">Kategori kriya</label>
            <select id="category_id" name="category_id">
                <option value="">— Tanpa kategori —</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= esc((string) $category['id']) ?>"
                        <?= (string) old('category_id', $editing ? (string) ($product['category_id'] ?? '') : '') === (string) $category['id'] ? 'selected' : '' ?>>
                        <?= esc((string) $category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="material">Bahan utama</label>
                <input id="material" name="material" maxlength="150"
                       value="<?= esc($value('material')) ?>"
                       placeholder="Kayu jati, tanah liat, emas">
            </div>

            <div class="lp-field">
                <label for="finishing">Finishing</label>
                <input id="finishing" name="finishing" maxlength="150"
                       value="<?= esc($value('finishing')) ?>"
                       placeholder="Cat, varnish, poles">
            </div>
        </div>
    </fieldset>

    <fieldset style="border:0;padding:0;margin:0 0 1.5rem;">
        <legend class="lp-field__label" style="margin-bottom:.75rem;">Harga dan stok</legend>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="price">Harga (Rp)</label>
                <input id="price" name="price" type="number" inputmode="numeric" min="0" required
                       value="<?= esc($value('price', '0')) ?>">
                <small class="lp-small lp-muted">Tanpa titik. Contoh: 450000</small>
            </div>

            <div class="lp-field">
                <label for="stock">Stok tersedia</label>
                <input id="stock" name="stock" type="number" inputmode="numeric" min="0"
                       value="<?= esc($value('stock', '0')) ?>">
                <small class="lp-small lp-muted">Diabaikan bila produk ini pesanan khusus.</small>
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="low_stock_threshold">Ambang stok menipis</label>
                <input id="low_stock_threshold" name="low_stock_threshold" type="number" min="0"
                       value="<?= esc($value('low_stock_threshold', '0')) ?>">
                <small class="lp-small lp-muted">Muncul di dasbor saat stok menyentuh angka ini.</small>
            </div>

            <div class="lp-field">
                <label for="production_days">Waktu pengerjaan (hari)</label>
                <input id="production_days" name="production_days" type="number" min="1" max="365"
                       value="<?= esc($value('production_days')) ?>">
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="weight_gram">Berat (gram)</label>
                <input id="weight_gram" name="weight_gram" type="number" min="0"
                       value="<?= esc($value('weight_gram')) ?>">
            </div>

            <div class="lp-field">
                <span class="lp-field__label">Jenis stok</span>

                <label class="lp-field--inline">
                    <input type="radio" name="made_to_order" value="0"
                        <?= old('made_to_order', $editing && $product['made_to_order'] ? '1' : '0') === '0' ? 'checked' : '' ?>>
                    Siap kirim
                </label>

                <label class="lp-field--inline">
                    <input type="radio" name="made_to_order" value="1"
                        <?= old('made_to_order', $editing && $product['made_to_order'] ? '1' : '0') === '1' ? 'checked' : '' ?>>
                    Pesanan khusus (dibuat setelah dipesan)
                </label>
            </div>
        </div>

        <div class="lp-field">
            <label class="lp-field--inline">
                <input type="checkbox" name="is_active" value="1"
                    <?= old('is_active', $editing ? (string) $product['is_active'] : '1') !== '0' ? 'checked' : '' ?>>
                Produk aktif dan bisa dibeli
            </label>
            <small class="lp-small lp-muted">
                Produk baru disimpan sebagai draf. Terbitkan lewat daftar produk setelah data lengkap.
            </small>
        </div>
    </fieldset>

    <div class="lp-row" style="gap:.5rem;">
        <button class="lp-btn lp-btn--solid" type="submit">
            <?= $editing ? 'Simpan perubahan' : 'Simpan produk' ?>
        </button>
        <a class="lp-btn lp-btn--ghost" href="<?= route_to('seller_products') ?>">Batal</a>
    </div>
</form>

<?php if ($editing): ?>
    <section class="lp-section">
        <h2>Pilihan ukuran</h2>
        <p class="lp-muted">
            <?php if ($product['variants'] === []): ?>
                Belum ada pilihan ukuran. Bila produk ini punya beberapa ukuran, tambahkan agar stok per
                ukuran bisa dilacak.
            <?php else: ?>
                <?= esc((string) count($product['variants'])) ?> pilihan ukuran tersimpan.
            <?php endif; ?>
        </p>

        <a class="lp-btn lp-btn--outline" href="<?= route_to('seller_variants', (string) $product['id']) ?>">
            Kelola pilihan ukuran
        </a>
    </section>

    <section class="lp-section">
        <h2>Foto produk</h2>

        <?php if ($product['images'] === []): ?>
            <p class="lp-muted">Belum ada foto. Produk tanpa foto masih bisa disimpan.</p>
        <?php else: ?>
            <div class="lp-grid lp-grid--three">
                <?php foreach ($product['images'] as $image): ?>
                    <figure class="lp-card__media" style="border-radius:var(--lp-radius);">
                        <img src="<?= esc((string) url_gambar($image['file_path'])) ?>"
                             alt="<?= esc((string) ($image['alt_text'] ?? '')) ?>"
                             loading="lazy" style="width:100%;aspect-ratio:1;object-fit:cover;">
                        <figcaption>
                            <form method="post"
                                  action="<?= route_to('seller_image_delete', (string) $image['id']) ?>"
                                  data-confirm="Hapus foto ini?">
                                <?= csrf_field() ?>
                                <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Hapus foto</button>
                            </form>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data"
              action="<?= route_to('seller_product_image', (string) $product['id']) ?>"
              style="margin-top:1rem;">
            <?= csrf_field() ?>
            <div class="lp-field">
                <label for="photo">Unggah foto</label>
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                <small class="lp-small lp-muted">
                    JPG, PNG, atau WebP. Maksimal 4 MB. File diperiksa ulang oleh server sebelum disimpan.
                </small>
            </div>
            <button class="lp-btn lp-btn--outline" type="submit">Unggah</button>
        </form>
    </section>
<?php endif; ?>

<?= $this->endSection() ?>
