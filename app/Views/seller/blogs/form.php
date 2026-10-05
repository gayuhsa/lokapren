<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$editing = $post !== null;
$value   = static fn (string $key, mixed $default = ''): string => (string) old($key, $editing ? ($post[$key] ?? $default) : $default);

$currentStatus = (string) old(
    'status',
    $editing ? (string) ($post['status'] ?? 'draft') : 'draft'
);
?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a> &rsaquo;
    <a href="<?= route_to('seller_blogs') ?>">Cerita saya</a> &rsaquo;
    <?= $editing ? 'Ubah cerita' : 'Tulis cerita' ?>
</p>

<h1><?= $editing ? 'Ubah Cerita' : 'Tulis Cerita' ?></h1>

<p class="lp-muted">
    Cerita terbit terbuka untuk semua orang, termasuk pembeli yang belum Marketplace. Tulis yang
    benar-benar terjadi di workshop: bahan yang dipakai, yang gagal, dan berapa lama satu karya jadi.
</p>

<form method="post" action="<?= esc((string) $action) ?>" class="lp-form">
    <?= csrf_field() ?>

    <div class="lp-field">
        <label for="title">Judul</label>
        <input id="title" name="title" required maxlength="200"
               value="<?= esc($value('title')) ?>"
               placeholder="Contoh: Cara saya mengasapkan gebyok tanpa meratakan motif">
    </div>

    <div class="lp-field">
        <label for="excerpt">Ringkasan</label>
        <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"><?= esc($value('excerpt')) ?></textarea>
        <small class="lp-small lp-muted">
            Satu-dua kalimat yang muncul di daftar artikel dan hasil pencarian.
        </small>
    </div>

    <div class="lp-field">
        <label for="body">Isi cerita</label>
        <textarea id="body" name="body" rows="14" maxlength="20000"><?= esc($value('body')) ?></textarea>
        <small class="lp-small lp-muted">
            Baris kosong di antara paragraf dipertahankan. Tulis plaintext seperti biasa, bukan kode HTML.
        </small>
    </div>

    <div class="lp-form__row">
        <div class="lp-field">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="">— Tanpa kategori —</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= esc((string) $category['id']) ?>"
                        <?= (string) old('category_id', $editing ? (string) ($post['category_id'] ?? '') : '') === (string) $category['id'] ? 'selected' : '' ?>>
                        <?= esc((string) $category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lp-field">
            <label for="cover_path">Alamat foto sampul</label>
            <input id="cover_path" name="cover_path" maxlength="255"
                   value="<?= esc($value('cover_path')) ?>"
                   placeholder="cerita/gebyok-1.jpg">
            <small class="lp-small lp-muted">
                Boleh dikosongkan. Artikel tanpa sampul tetap terbit dengan kartu teks biasa.
            </small>
        </div>
    </div>

    <div class="lp-field">
        <span class="lp-field__label">Status</span>

        <label class="lp-field--inline">
            <input type="radio" name="status" value="draft"
                <?= $currentStatus === 'draft' ? 'checked' : '' ?>>
            Simpan sebagai draf
        </label>

        <label class="lp-field--inline">
            <input type="radio" name="status" value="published"
                <?= $currentStatus === 'published' ? 'checked' : '' ?>>
            Terbitkan
        </label>

        <?php if ($editing && ! empty($post['published_at'])): ?>
            <small class="lp-small lp-muted">
                Terbit terakhir pada <?= format_tanggal($post['published_at'], true) ?>.
            </small>
        <?php endif; ?>
    </div>

    <div class="lp-row" style="gap:.5rem;">
        <button class="lp-btn lp-btn--solid" type="submit">
            <?= $editing ? 'Simpan perubahan' : 'Simpan cerita' ?>
        </button>
        <a class="lp-btn lp-btn--ghost" href="<?= route_to('seller_blogs') ?>">Batal</a>
    </div>
</form>

<?= $this->endSection() ?>
