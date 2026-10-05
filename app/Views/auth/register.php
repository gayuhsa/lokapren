<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="lp-auth">
    <div class="lp-auth__card lp-auth__card--wide">
        <div class="lp-auth__head">
            <h1>Daftar ke Lokapren</h1>
            <p class="lp-muted">Beli karya kerajinan tangan, atau tawarkan karyamu ke pembeli di Magelang.</p>
        </div>


        <form method="post" action="<?= route_to('register') ?>" autocomplete="on">
            <?= csrf_field() ?>

            <fieldset style="border:0;padding:0;margin:0 0 1rem;">
                <legend class="lp-field__label" style="margin-bottom:.5rem;">Saya ingin</legend>

                <div class="lp-role-choice">
                    <label class="lp-radio-card">
                        <input type="radio" name="role" value="customer"
                               <?= old('role', 'customer') === 'customer' ? 'checked' : '' ?>>
                        <span>
                            <strong>Beli karya</strong><br>
                            <span class="lp-muted">Saya mencari produk dari pengrajin.</span>
                        </span>
                    </label>

                    <label class="lp-radio-card">
                        <input type="radio" name="role" value="seller"
                               <?= old('role') === 'seller' ? 'checked' : '' ?>>
                        <span>
                            <strong>Jual karya</strong><br>
                            <span class="lp-muted">Saya seorang pengrajin dan ingin membuka toko.</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div class="lp-form__row">
                <div class="lp-field">
                    <label for="full_name">Nama lengkap</label>
                    <input id="full_name" type="text" name="full_name" autocomplete="name"
                           value="<?= esc(old('full_name')) ?>" required>
                </div>

                <div class="lp-field">
                    <label for="username">Nama pengguna</label>
                    <input id="username" type="text" name="username" autocomplete="username"
                           value="<?= esc(old('username')) ?>" required>
                    <small class="lp-hint">Dipakai untuk masuk bersama email.</small>
                </div>
            </div>

            <div class="lp-field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" autocomplete="email"
                       value="<?= esc(old('email')) ?>" required>
            </div>

            <div class="lp-form__row">
                <div class="lp-field">
                    <label for="password">Kata sandi</label>
                    <input id="password" type="password" name="password"
                           autocomplete="new-password" required>
                </div>

                <div class="lp-field">
                    <label for="password_confirm">Ulangi kata sandi</label>
                    <input id="password_confirm" type="password" name="password_confirm"
                           autocomplete="new-password" required>
                </div>
            </div>

            <button class="lp-btn lp-btn--solid lp-btn--block" type="submit">Buat Akun</button>
        </form>

        <p class="lp-small lp-muted" style="margin-top:1rem;text-align:center;">
            Sudah punya akun?
            <a href="<?= route_to('login') ?>">Masuk di sini</a>
        </p>
    </div>
</div>

<?= $this->endSection() ?>
