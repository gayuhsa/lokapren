<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$profile  = $shop['profile'];
$editing  = static fn (string $key, mixed $default = ''): string => (string) old($key, (string) ($profile[$key] ?? $default));

// The hours rows are keyed by ISO weekday so the form can loop 1..7 without
// caring which days a seller has actually filled in.
$hoursByDay = [];

foreach ($shop['hours'] as $row) {
    $hoursByDay[(int) $row['day_of_week']] = $row;
}

$dayNames    = nama_hari();
$activeCodes = array_column($shop['facilities'], 'facility');
?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a> &rsaquo; Toko saya
</p>

<div class="lp-section__head">
    <div>
        <h1>Toko Saya</h1>
        <p class="lp-muted">
            Semua yang ada di sini muncul di halaman publik toko Anda.
            Status saat ini:
            <?php if ($shop['is_open']): ?>
                <span class="lp-pill lp-pill--ok">Buka sekarang</span>
            <?php else: ?>
                <span class="lp-pill lp-pill--warn">Tutup</span>
            <?php endif; ?>
        </p>
    </div>

    <a class="lp-btn lp-btn--outline" href="<?= route_to('storefront', (string) $profile['slug']) ?>">
        Lihat halaman publik
    </a>
</div>

<section class="lp-section">
    <h2>Identitas toko</h2>

    <form method="post" action="<?= route_to('seller_shop_update') ?>" class="lp-form">
        <?= csrf_field() ?>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="display_name">Nama toko</label>
                <input id="display_name" name="display_name" required maxlength="150"
                       value="<?= esc($editing('display_name')) ?>">
                <small class="lp-small lp-muted">Muncul di header toko dan di peta lokasi.</small>
            </div>

            <div class="lp-field">
                <label for="owner_name">Nama pemilik</label>
                <input id="owner_name" name="owner_name" maxlength="150"
                       value="<?= esc($editing('owner_name')) ?>">
            </div>
        </div>

        <div class="lp-field">
            <label for="tagline">Tagline</label>
            <input id="tagline" name="tagline" maxlength="255"
                   value="<?= esc($editing('tagline')) ?>"
                   placeholder="Gebyok tulap Temanggung, dibuat satu per satu">
        </div>

        <div class="lp-field">
            <label for="craft_focus">Fokus kriya</label>
            <input id="craft_focus" name="craft_focus" maxlength="150"
                   value="<?= esc($editing('craft_focus')) ?>"
                   placeholder="Gebyok, ukir tulap, tableware">
        </div>

        <div class="lp-field">
            <label for="description">Cerita singkat toko</label>
            <textarea id="description" name="description" rows="5" maxlength="3000"><?= esc($editing('description')) ?></textarea>
        </div>

        <div class="lp-field">
            <label class="lp-field--inline">
                <input type="checkbox" name="is_active" value="1"
                    <?= old('is_active', (string) ($profile['is_active'] ?? '1')) !== '0' ? 'checked' : '' ?>>
                Tampilkan toko di katalog dan peta lokasi
            </label>
        </div>

        <h3>Lokasi</h3>
        <p class="lp-small lp-muted">
            Alamat yang ditulis bebas, tidak perlu lengkap. Yang penting pembeli bisa menemukan
            tempatnya di peta.
        </p>

        <div class="lp-field">
            <label for="address_line">Alamat jalan</label>
            <input id="address_line" name="address_line" maxlength="255"
                   value="<?= esc($editing('address_line')) ?>">
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="village">Desa / dukuh</label>
                <input id="village" name="village" maxlength="100" value="<?= esc($editing('village')) ?>">
            </div>

            <div class="lp-field">
                <label for="district">Kecamatan</label>
                <input id="district" name="district" maxlength="100" value="<?= esc($editing('district')) ?>">
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="regency">Kabupaten</label>
                <input id="regency" name="regency" maxlength="100" value="<?= esc($editing('regency')) ?>">
            </div>

            <div class="lp-field">
                <label for="province">Provinsi</label>
                <input id="province" name="province" maxlength="100" value="<?= esc($editing('province')) ?>">
            </div>

            <div class="lp-field">
                <label for="postal_code">Kode pos</label>
                <input id="postal_code" name="postal_code" maxlength="10" inputmode="numeric"
                       value="<?= esc($editing('postal_code')) ?>">
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="landmark_name">Titik yang mudah dikenali</label>
                <input id="landmark_name" name="landmark_name" maxlength="150"
                       value="<?= esc($editing('landmark_name')) ?>">
            </div>

            <div class="lp-field">
                <label for="landmark_distance_km">Jarak ke titik itu (km)</label>
                <input id="landmark_distance_km" name="landmark_distance_km" type="number" step="0.1" min="0"
                       value="<?= esc($editing('landmark_distance_km')) ?>">
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="latitude">Lintang (latitude)</label>
                <input id="latitude" name="latitude" type="number" step="0.000001" min="-90" max="90"
                       value="<?= esc($editing('latitude')) ?>">
            </div>

            <div class="lp-field">
                <label for="longitude">Bujur (longitude)</label>
                <input id="longitude" name="longitude" type="number" step="0.000001" min="-180" max="180"
                       value="<?= esc($editing('longitude')) ?>">
                <small class="lp-small lp-muted">
                    Isi keduanya atau kosongkan keduanya. Kalau hanya satu, pin-nya diabaikan.
                </small>
            </div>
        </div>

        <button class="lp-btn lp-btn--solid" type="submit">Simpan identitas</button>
    </form>
</section>

<section class="lp-section">
    <h2>Jam buka</h2>

    <form method="post" action="<?= route_to('seller_hours_update') ?>" class="lp-form">
        <?= csrf_field() ?>

        <div class="lp-table-wrap">
            <table class="lp-table">
                <thead>
                    <tr>
                        <th scope="col">Hari</th>
                        <th scope="col">Buka</th>
                        <th scope="col">Tutup</th>
                        <th scope="col">Libur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($day = 1; $day <= 7; $day++):
                        $row        = $hoursByDay[$day] ?? [];
                        $dayKey     = (string) $day;
                        $isClosed   = (int) ($row['is_closed'] ?? 0) === 1;
                        ?>
                        <tr>
                            <th scope="row"><?= esc($dayNames[$day]) ?></th>
                            <td>
                                <input type="time" name="opens_at[<?= esc($dayKey) ?>]"
                                       value="<?= esc((string) old("opens_at.{$dayKey}", substr((string) ($row['opens_at'] ?? ''), 0, 5))) ?>">
                            </td>
                            <td>
                                <input type="time" name="closes_at[<?= esc($dayKey) ?>]"
                                       value="<?= esc((string) old("closes_at.{$dayKey}", substr((string) ($row['closes_at'] ?? ''), 0, 5))) ?>">
                            </td>
                            <td>
                                <label class="lp-field--inline">
                                    <input type="checkbox" name="is_closed[<?= esc($dayKey) ?>]" value="1"
                                        <?= old("is_closed.{$dayKey}", $isClosed ? '1' : '') === '1' ? 'checked' : '' ?>>
                                    Libur
                                </label>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <button class="lp-btn lp-btn--solid" type="submit" style="margin-top:1rem;">Simpan jam buka</button>
    </form>
</section>

<section class="lp-section">
    <h2>Fasilitas</h2>
    <p class="lp-muted">
        Fasilitas yang dicentang muncul sebagai filter di halaman lokasi dan sebagai catatan di toko Anda.
    </p>

    <form method="post" action="<?= route_to('seller_facilities_update') ?>">
        <?= csrf_field() ?>

        <div class="lp-grid lp-grid--two">
            <?php foreach ($shop['facility_choices'] as $code => $label): ?>
                <label class="lp-field--inline">
                    <input type="checkbox" name="facilities[]" value="<?= esc((string) $code) ?>"
                        <?= in_array((string) $code, $activeCodes, true) ? 'checked' : '' ?>>
                    <?= esc((string) $label) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button class="lp-btn lp-btn--solid" type="submit" style="margin-top:1rem;">Simpan fasilitas</button>
    </form>
</section>

<section class="lp-section">
    <h2>Bagian cerita toko</h2>
    <p class="lp-muted">
        Bagian ini muncul berurutan di halaman toko, biasanya untuk menjelaskan proses, bahan, atau asal karya.
    </p>

    <?php if ($shop['stories'] === []): ?>
        <p class="lp-muted">Belum ada bagian cerita.</p>
    <?php else: ?>
        <div class="lp-stack" style="margin-bottom:1.5rem;">
            <?php foreach ($shop['stories'] as $story): ?>
                <div class="lp-panel">
                    <strong><?= esc((string) $story['title']) ?></strong>
                    <?php if (! empty($story['subtitle'])): ?>
                        <div class="lp-small lp-muted"><?= esc((string) $story['subtitle']) ?></div>
                    <?php endif; ?>
                    <?php if (! empty($story['body'])): ?>
                        <p class="lp-small"><?= esc(mb_strimwidth((string) $story['body'], 0, 180, '…')) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= route_to('seller_story_update') ?>" class="lp-form">
        <?= csrf_field() ?>

        <div class="lp-field">
            <label for="story_title">Judul bagian</label>
            <input id="story_title" name="title" required maxlength="150" value="<?= esc(old('title')) ?>">
        </div>

        <div class="lp-field">
            <label for="story_subtitle">Subjudul</label>
            <input id="story_subtitle" name="subtitle" maxlength="255" value="<?= esc(old('subtitle')) ?>">
        </div>

        <div class="lp-field">
            <label for="story_body">Isi</label>
            <textarea id="story_body" name="body" rows="5" maxlength="5000"><?= esc(old('body')) ?></textarea>
        </div>

        <button class="lp-btn lp-btn--outline" type="submit">Tambah bagian cerita</button>
    </form>
</section>

<section class="lp-section">
    <h2>Foto dan video toko</h2>

    <?php if ($shop['media'] === []): ?>
        <p class="lp-muted">Belum ada media.</p>
    <?php else: ?>
        <div class="lp-grid lp-grid--three">
            <?php foreach ($shop['media'] as $media): ?>
                <figure class="lp-card__media" style="border-radius:var(--lp-radius);">
                    <?php if ((string) $media['media_type'] === 'video'): ?>
                        <video controls preload="metadata"
                               style="width:100%;aspect-ratio:4/3;object-fit:cover;"
                               src="<?= esc((string) url_gambar($media['file_path'])) ?>"></video>
                    <?php else: ?>
                        <img src="<?= esc((string) url_gambar($media['thumbnail_path'] ?: $media['file_path'])) ?>"
                             alt="<?= esc((string) ($media['title'] ?? '')) ?>"
                             loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;">
                    <?php endif; ?>

                    <figcaption class="lp-small lp-muted">
                        <?= esc((string) ($media['title'] ?? 'Tanpa judul')) ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data"
          action="<?= route_to('seller_media_update') ?>" class="lp-form" style="margin-top:1rem;">
        <?= csrf_field() ?>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="media_type">Jenis</label>
                <select id="media_type" name="media_type">
                    <option value="photo">Foto</option>
                    <option value="video">Video</option>
                </select>
            </div>

            <div class="lp-field">
                <label for="media">Berkas</label>
                <input id="media" name="media" type="file" required
                       accept="image/jpeg,image/png,image/webp,video/mp4">
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="media_title">Judul</label>
                <input id="media_title" name="title" maxlength="150" value="<?= esc(old('title')) ?>">
            </div>

            <div class="lp-field">
                <label for="media_caption">Keterangan</label>
                <input id="media_caption" name="caption" maxlength="255" value="<?= esc(old('caption')) ?>">
            </div>
        </div>

        <button class="lp-btn lp-btn--outline" type="submit">Unggah media</button>
    </form>
</section>

<section class="lp-section">
    <h2>Balasan cepat</h2>
    <p class="lp-muted">
        Potongan kalimat yang bisa Anda pakai saat membalas chat, supaya tidak mengetik dari nol.
    </p>

    <?php if ($shop['quick_replies'] === []): ?>
        <p class="lp-muted">Belum ada balasan cepat.</p>
    <?php else: ?>
        <div class="lp-stack" style="margin-bottom:1.5rem;">
            <?php foreach ($shop['quick_replies'] as $reply): ?>
                <div class="lp-panel lp-panel__head">
                    <div>
                        <strong><?= esc((string) $reply['title']) ?></strong>
                        <div class="lp-small lp-muted"><?= esc((string) $reply['body']) ?></div>
                    </div>

                    <form method="post"
                          action="<?= route_to('seller_quick_reply_delete', (string) $reply['id']) ?>"
                          data-confirm="Hapus balasan cepat ini?">
                        <?= csrf_field() ?>
                        <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Hapus</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= route_to('seller_quick_reply_store') ?>" class="lp-form">
        <?= csrf_field() ?>

        <div class="lp-field">
            <label for="reply_title">Judul</label>
            <input id="reply_title" name="title" required maxlength="60" value="<?= esc(old('title')) ?>">
        </div>

        <div class="lp-field">
            <label for="reply_body">Isi balasan</label>
            <textarea id="reply_body" name="body" rows="3" maxlength="500" required><?= esc(old('body')) ?></textarea>
        </div>

        <button class="lp-btn lp-btn--outline" type="submit">Tambah balasan cepat</button>
    </form>
</section>

<?= $this->endSection() ?>
