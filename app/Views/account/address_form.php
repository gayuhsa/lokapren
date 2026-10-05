<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = $address !== null;
$value  = static fn (string $key): string => (string) old($key, $address[$key] ?? '');
?>

<p class="lp-breadcrumb">
    <a href="<?= route_to('addresses') ?>">Alamat</a> &rsaquo;
    <?= $isEdit ? 'Ubah alamat' : 'Tambah alamat' ?>
</p>

<h1><?= $isEdit ? 'Ubah Alamat' : 'Tambah Alamat' ?></h1>


<div class="lp-panel" style="max-width:44rem;">
    <p class="lp-muted lp-small">
        Isi nama jalan, desa, kecamatan, dan kabupaten sesuai alamat Anda.
        Tidak ada daftar wilayah yang perlu dipilih.
    </p>

    <form method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="label">Label alamat</label>
                <input id="label" type="text" name="label" maxlength="40" required
                       value="<?= esc($value('label')) ?>" placeholder="Rumah, Kantor, Kos">
            </div>

            <div class="lp-field">
                <label for="recipient_name">Nama penerima</label>
                <input id="recipient_name" type="text" name="recipient_name" maxlength="150" required
                       value="<?= esc($value('recipient_name')) ?>">
            </div>
        </div>

        <div class="lp-field">
            <label for="recipient_phone">Nomor WhatsApp</label>
            <input id="recipient_phone" type="tel" name="recipient_phone" maxlength="25" required
                   value="<?= esc($value('recipient_phone')) ?>" placeholder="08xx-xxxx-xxxx"
                   inputmode="tel">
        </div>

        <div class="lp-field">
            <label for="address_line">Alamat lengkap</label>
            <textarea id="address_line" name="address_line" rows="3" maxlength="255" required
                      placeholder="Nama jalan, nomor rumah, RT/RW"><?= esc($value('address_line')) ?></textarea>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="village">Desa / kelurahan</label>
                <input id="village" type="text" name="village" maxlength="100"
                       value="<?= esc($value('village')) ?>">
            </div>

            <div class="lp-field">
                <label for="district">Kecamatan</label>
                <input id="district" type="text" name="district" maxlength="100"
                       value="<?= esc($value('district')) ?>">
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="regency">Kabupaten / kota</label>
                <input id="regency" type="text" name="regency" maxlength="100"
                       value="<?= esc($value('regency')) ?>" placeholder="Magelang">
            </div>

            <div class="lp-field">
                <label for="province">Provinsi</label>
                <input id="province" type="text" name="province" maxlength="100"
                       value="<?= esc($value('province')) ?>" placeholder="Jawa Tengah">
            </div>
        </div>

        <div class="lp-form__row">
            <div class="lp-field">
                <label for="postal_code">Kode pos</label>
                <input id="postal_code" type="text" name="postal_code" maxlength="10"
                       value="<?= esc($value('postal_code')) ?>" inputmode="numeric">
            </div>

            <div class="lp-field">
                <label for="landmark">Patokan</label>
                <input id="landmark" type="text" name="landmark" maxlength="150"
                       value="<?= esc($value('landmark')) ?>" placeholder="Depan masjid, dekat balai desa">
            </div>
        </div>

        <div class="lp-field">
            <label for="delivery_notes">Catatan untuk kurir</label>
            <textarea id="delivery_notes" name="delivery_notes" rows="2" maxlength="255"
                      placeholder="Contoh: titip ke satpam bila rumah kosong"><?= esc($value('delivery_notes')) ?></textarea>
        </div>

        <fieldset style="border:0;padding:0;margin:0 0 1rem;">
            <legend class="lp-field__label">Titik peta (opsional)</legend>
            <p class="lp-hint">
                Isi kedua koordinat bila ingin melihat alamat di peta. Alamat tanpa
                koordinat tetap bisa disimpan.
            </p>

            <div class="lp-form__row">
                <div class="lp-field">
                    <label for="latitude">Latitude</label>
                    <input id="latitude" type="number" name="latitude" step="0.0000001"
                           min="-90" max="90"
                           value="<?= esc($value('latitude')) ?>" placeholder="-7.5422">
                </div>

                <div class="lp-field">
                    <label for="longitude">Longitude</label>
                    <input id="longitude" type="number" name="longitude" step="0.0000001"
                           min="-180" max="180"
                           value="<?= esc($value('longitude')) ?>" placeholder="110.4448">
                </div>
            </div>
        </fieldset>

        <label class="lp-field--inline" style="margin-bottom:.5rem;">
            <input type="checkbox" name="is_hotel" value="1"
                   <?= (string) old('is_hotel', (string) ($address['is_hotel'] ?? '')) === '1' ? 'checked' : '' ?>>
            <span>Alamat di hotel atau penginapan</span>
        </label>

        <?php if ($isEdit && ! $address['is_default']): ?>
            <label class="lp-field--inline" style="margin-bottom:1rem;">
                <input type="checkbox" name="is_default" value="1" <?= old('is_default') === '1' ? 'checked' : '' ?>>
                <span>Jadikan alamat utama</span>
            </label>
        <?php endif; ?>

        <div class="lp-row">
            <button class="lp-btn lp-btn--solid" type="submit">
                <?= $isEdit ? 'Simpan perubahan' : 'Simpan alamat' ?>
            </button>
            <a class="lp-btn lp-btn--ghost" href="<?= route_to('addresses') ?>">Batal</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
